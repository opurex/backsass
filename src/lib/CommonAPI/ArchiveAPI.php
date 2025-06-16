<?php
//    Pastèque API
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\CommonAPI;

use \Pasteque\Server\API\FiscalAPI;
use \Pasteque\Server\Exception\ConfigurationException;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Archive;
use \Pasteque\Server\Model\ArchiveRequest;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\GenericModel;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOCondition;

class ArchiveAPI implements \Pasteque\Server\API\API
{
    /** The maximum number of fiscal tickets in a single file of an archive. */
    const BATCH_SIZE = 100;

    protected $dao;
    protected $gpg;
    protected $account;
    protected $fingerprint;
    protected $signEnabled;

    public function __construct($dao, $account, $path, $fingerprint) {
        $this->dao = $dao;
        $this->account = $account;
        putenv(sprintf('GNUPGHOME=%s', $path));
        $this->gpg = new \gnupg();
        if (!$this->gpg->addsignkey($fingerprint)) {
            $this->signEnabled = false;
        } else {
            $this->signEnabled = true;
        }
        $this->gpg->setarmor(0);
        $this->gpg->setsignmode(\GNUPG_SIG_MODE_NORMAL);
        $this->fingerprint = $fingerprint;
    }

    public static function fromApp($app) {
        if (!$app->isGPGEnabled()) {
            return false;
        }
        return new static($app->getDao(), $app->getCurrentUser()['id'],
                $app->getGPGPath(), $app->getKeyFingerprint());
    }

    public function canSign() {
        return $this->signEnabled;
    }

    public function getFingerprint() {
        return $this->fingerprint;
    }

    public function getAccount() {
        return $this->account;
    }

    /** Get all the requests diregarding their state. */
    public function getRequests() {
        return $this->dao->search(ArchiveRequest::class, null, null, null,
            'id');
    }

    /**
     * Get the first request that is not currently being processed.
     * If a request is currently being generated, don't get an other one
     * to prevent holding the cpu.
     */
    public function getFirstAvailableRequest() {
        $searchProcessing = $this->dao->search(ArchiveRequest::class,
                new DAOCondition('processing', '=', true), 1);
        if (count($searchProcessing) > 0) {
            return null;
        }
        $search = $this->dao->search(ArchiveRequest::class,
                new DAOCondition('processing', '=', false),
                1, null, 'id');
        if (count($search) == 1) {
            return $search[0];
        } else {
            return null;
        }
    }

    /**
     * Register a single request.
     * @param mixed $start The representation of the starting date, compatible
     * with DateUtils::readDate.
     * @param mixed $stop The representation of the stop date, compatible with
     * DateUtils::readDate.
     * @throws InvalidFieldException, with constraint CSTR_INVALID_DATE when
     * $start or $stop could not be read as a date or one is in the future;
     * with constraint CSTR_INVALID_DATERANGE when stop is anterior to start
     * or when the time interval is greater than one year + one day.
     */
    public function addRequest($start, $stop) {
        // Defensive checks
        $startDate = DateUtils::readDate($start);
        $stopDate = DateUtils::readDate($stop);
        // Valid date check
        if ($startDate === false || $startDate === null) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'startDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $start);
        }
        if ($stopDate === false || $stopDate === null) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'stopDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $stop);
        }
        // Past dates check
        $now = new \DateTime();
        if ($now->getTimestamp() < $startDate->getTimestamp()) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'startDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $start);
        }
        if ($now->getTimestamp() < $stopDate->getTimestamp()) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'stopDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $stop);
        }
        // Less than 1 year interval check
        $interval = $startDate->diff($stopDate);
        if ($interval->invert === 1) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATERANGE,
                    ArchiveRequest::class, 'startDate-stopDate',
                    ['startDate' => $startDate->format('Y-m-d H:i:s'),
                    'stopDate' => $stopDate->format('Y-m-d H:i:s')],
                    $interval->format('-%y-%m-%d %H:%i:%s'));
        }
        if ($interval->y > 1
                || (($interval->y == 1) && ($interval->m > 0 ||
                        ($interval->m == 0 && $interval->d > 0)))) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATERANGE,
                    ArchiveRequest::class, 'startDate-stopDate',
                    ['startDate' => $start->format('Y-m-d H:i:s'),
                    'stopDate' => $stop->format('Y-m-d H:i:s')],
                    $interval->format('%y-%m-%d %H:%i:%s'));
        }
        // Write the request
        $request = new ArchiveRequest();
        $request->setStartDate($startDate);
        $request->setStopDate($stopDate);
        $this->dao->write($request);
        $this->dao->commit();
        return $request;
    }

    /**
     * Mark an existing request being processed.
     * @return True when the request was updated. False when the request
     * was not previously registered.
     */
    private function markProcessing($request) {
        $snap = $this->dao->readSnapshot(ArchiveRequest::class,
                $request->getId());
        if ($snap === null) {
            return false;
        }
        // Copy to be sure the request is not updated outside processing.
        $request->setStartDate($snap->getStartDate());
        $request->setStopDate($snap->getStopDate());
        $request->setProcessing(true);
        $this->dao->write($request);
        $this->dao->commit();
        return true;
    }

    /**
     * Get the list of [number = <id>, info = [],
     * signature = <sign>] of each existing archives.
     * @return GenericModel[] The list of archives.
     */
    public function listArchives() {
        $em = $this->dao->getEntityManager();
        $query = $em->createQuery('select a.number, a.info from ' . Archive::class . ' a order by a.number asc');
        $archives = $query->getResult();
        $ret = [];
        foreach ($archives as $a) {
            $obj = new GenericModel();
            $obj->set('number', $a['number']);
            $info = json_decode($a['info'], true);
            if ($info != null) {
                $infoObj = new GenericModel();
                foreach ($info as $k => $v) {
                    $infoObj->set($k, $v);
                }
                $obj->set('info', $infoObj);
            }
            $ret[] = $obj;
        }
        return $ret;
    }

    public function getArchive($number) {
        return $this->dao->read(Archive::class, $number);
    }

    public function getArchiveContent($number) {
        $archive = $this->dao->read(Archive::class, $number);
        if ($archive == null) {
            return null;
        }
        return $archive->getContent();
    }

    public function getLastArchive() {
        $lastArchive = null;
        $lastSearch = $this->dao->search(Archive::class, null, 1, null,
                '-number');
        if (count($lastSearch) > 0) {
            $lastArchive = $lastSearch[0];
            if ($lastArchive->getNumber() === 0) {
                // Ignore EOS.
                return null;
            }
            return $lastArchive;
        }
        // No archive found.
        return null;
    }

    protected function checkSignature($fTkt, $prevFTkt) {
        if ($fTkt->getNumber() == 1) {
            return $fTkt->checkSignature(null);
        }
        if ($prevFTkt == null) {
            $prevFTkt = $this->dao->read(FiscalTicket::class,
                    ['type' => $fTkt->getType(),
                    'sequence' => $fTkt->getSequence(),
                    'number' => $fTkt->getNumber() - 1]);
        }
        if ($prevFTkt != null) {
            return $fTkt->checkSignature($prevFTkt);
        } else {
            return false;
        }
    }

    /**
     * Generate an archive from a request. The request must have been
     * registered and not currently being processed.
     * The request is marked being processed, the archive is generated and
     * the request is deleted.
     * @return True when everything went well. False if the archive couldn't
     * be generated.
     * @throws RecordNotFoundException When no request can be found with the
     * given number.
     * @throws ConfigurationException When the signing key is not available.
     */
    public function createArchive($requestNumber) {
        if (!$this->canSign()) {
            throw new ConfigurationException('gpg/fingerprint',
                    $this->fingerprint,
                    'Could not use this signing key. Is it imported in the keyring and has no passphrase?');
        }
        $request = $this->dao->readSnapshot(ArchiveRequest::class,
                $requestNumber);
        if ($request == null) {
            throw new RecordNotFoundException(ArchiveRequest::class,
                    ['id' => $requestNumber]);
        }
        $request = $this->dao->read(ArchiveRequest::class, $requestNumber);
        if ($request->isProcessing() || !$this->markProcessing($request)) {
            return false;
        }
        try {
            $ftAPI = new FiscalAPI($this->dao);
            $sequences = $ftAPI->getSequences();
            $allFileNames = [];
            // Create files for each types and sequences
            $types = $ftAPI->getTypes();
            foreach ($sequences as $seq) {
                foreach ($types as $type) {
                    // Get all fiscal tickets
                    // Run by batches to limit memory consumption
                    $batchSize = static::BATCH_SIZE;
                    $page = 0;
                    $done = false;
                    $found = false;
                    $searchConds = [
                            new DAOCondition('date', '>=', $request->getStartDate()),
                            new DAOCondition('date', '<=', $request->getStopDate()),
                            new DAOCondition('type', '=', $type),
                            new DAOCondition('sequence', '=', $seq),
                            new DAOCondition('number', '!=', 0)
                    ];
                    $prevTkt = null;
                    while (!$done) {
                        $tickets = $ftAPI->search($searchConds, $batchSize,
                                $batchSize * $page,
                                ['type', 'sequence', 'number']);
                        // Convert tickets to struct
                        if (count($tickets) == 0) {
                            $done = true;
                        } else {
                            $found = true;
                            $tkts = [];
                            foreach ($tickets as $tkt) {
                                $struct = $tkt->toStruct();
                                $struct['signature_ok'] = $this->checkSignature($tkt, $prevTkt);
                                if (is_numeric($struct['date'])) {
                                    $date = new \DateTime();
                                    $date->setTimestamp($struct['date']);
                                    $struct['date'] = $date->format('Y-m-d H:i:s');
                                }
                                $tkts[] = $struct;
                                $prevTkt = $tkt;
                            }
                            $name = sprintf('%s-%s-%d.txt',
                                    $type, $seq, ($page + 1));
                            $fileName = tempnam(sys_get_temp_dir(), $name);
                            $file = fopen($fileName, 'w'); 
                            $strData = json_encode($tkts);
                            fwrite($file, $strData);
                            $allFileNames[] = [
                                    'file' => $fileName,
                                    'name' => $name
                            ];
                            fclose($file);
                        }
                        $page++;
                    }
                }
            }
            $now = new \DateTime();
            $lastArchive = $this->getLastArchive();
            $archNumber = 1;
            if ($lastArchive !== null) {
                $archNumber = $lastArchive->getNumber() + 1;
            }
            // Create the meta file
            $metaFilename = tempnam(sys_get_temp_dir(), 'archive.txt');
            $metaFile = fopen($metaFilename, 'w');
            $metaData = json_encode([
                    'account' => $this->account,
                    'dateStart' => $request->getStartDate()->format('Y-m-d H:i:s'),
                    'dateStop' => $request->getStopDate()->format('Y-m-d H:i:s'),
                    'generated' => $now->format('Y-m-d H:i:s'),
                    'number' => $archNumber,
                    'purged' => false,
            ]);
            fwrite($metaFile, $metaData);
            $allFileNames[] = [
                    'file' => $metaFilename,
                    'name' => 'archive.txt'
            ];
            fclose($metaFile);
            // Create a zip file with all the ticket files
            $zipFileName = tempnam(sys_get_temp_dir(), 'archive.zip');
            $zip = new \ZipArchive();
            $zip->open($zipFileName, \ZipArchive::CREATE);
            foreach ($allFileNames as $f) {
                $zip->addFile($f['file'], $f['name']);
            }
            $zip->close();
            $zipContent = file_get_contents($zipFileName);
            // Sign
            $signedContent = $this->gpg->sign($zipContent);
            // Write
            $archive = new Archive();
            $archive->setInfo($metaData);
            $archive->setContent($signedContent);
            $archive->setNumber($archNumber);
            $archive->sign($lastArchive);
            $this->dao->write($archive);
            $this->dao->delete($request);
            $this->updateEOSArchive($archive);
            $this->dao->commit();
            // Clean
            foreach ($allFileNames as $f) {
                unlink($f['file']);
            }
            unlink($zipFileName);
            return $archive;
        } catch (Exception $e) {
            if ($this->dao->readSnapshot(Archive::class, $requestNumber) == null) {
                $request->setProcessing(false);
                $this->dao->write($request);
                $this->dao->commit();
            }
            throw $e;
        }
    }

    /**
     * Update the signature of the end-of-sequence Archive with
     * the last archive created. Does not commit.
     * @param \Pasteque\Server\Model\Archive $lastArchive The last archive.
     */
    protected function updateEOSArchive($lastArchive) {
        if ($lastArchive === null) {
            // TODO: warning
            return;
        }
        $eosArchive = $this->dao->read(Archive::class, 0);
        if ($eosArchive === null) {
            $eosArchive = new Archive();
            $eosArchive->setNumber(0);
            $eosArchive->setInfo('EOS');
            $eosArchive->setContent('EOS');
        }
        $eosArchive->sign($lastArchive);
        $this->dao->write($eosArchive);
    }
}
