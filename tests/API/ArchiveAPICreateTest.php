<?php
//    Pasteque server testing
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.
namespace Pasteque\Server;

use \Pasteque\Server\CommonAPI\ArchiveAPI;
use \Pasteque\Server\Exception\ConfigurationException;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Archive;
use \Pasteque\Server\Model\ArchiveRequest;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");
require_once(dirname(dirname(__FILE__)) . "/PastequeTestCase.php");

class ArchiveAPICreateTest extends PastequeTestCase
{
    private $dao;
    private $api;
    private $pubKey;
    private $privKey;

    protected function setUp(): void {
        global $dbInfo;
        global $cfg;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $gpgPath = null;
        $basePath = dirname(dirname(dirname(__FILE__)));
        if (substr($cfg['gpg/path'], 0, 1) == '.') {
            $gpgPath = $basePath . '/'. $cfg['gpg/path'];
        } else {
            $gpgPath = $cfg['gpg/path'];
        }
        $this->api = new ArchiveAPI($this->dao, $cfg['http/user'], $gpgPath,
                $cfg['gpg/fingerprint']);
    }

    protected function tearDown(): void {
        foreach ([ArchiveRequest::class, Archive::class, FiscalTicket::class]
                as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    protected function createFT($date, $type, $sequence, $number, $content,
            $previous) {
        $ft = new FiscalTicket();
        $ft->setType($type);
        $ft->setDate($date);
        $ft->setSequence($sequence);
        $ft->setNumber($number);
        $ft->setContent($content);
        $ft->sign($previous);
        return $ft;
    }

    protected function createZFiscalTicket($date, $sequence, $number, $content,
            $previous) {
        return $this->createFT($date, FiscalTicket::TYPE_ZTICKET, $sequence,
                $number, $content, $previous);
    }

    protected function createFiscalTicket($date, $sequence, $number, $content,
            $previous) {
        return $this->createFT($date, FiscalTicket::TYPE_TICKET, $sequence,
                $number, $content, $previous);
    }

    protected function createCustomFiscalTicket($date, $sequence, $number,
            $content, $previous) {
        return $this->createFT($date, 'custom type', $sequence, $number,
                $content, $previous);
    }


    public function testNoKey() {
        global $cfg;
        $gpgPath = null;
        $basePath = dirname(dirname(dirname(__FILE__)));
        if (substr($cfg['gpg/path'], 0, 1) == '.') {
            $gpgPath = $basePath . '/'. $cfg['gpg/path'];
        } else {
            $gpgPath = $cfg['gpg/path'];
        }
        $exceptionThrown = false;
        $fingerprint = '1234123412341234123412341234123412341234';
        $this->api = new ArchiveAPI($this->dao, $cfg['http/user'],
                $gpgPath, $fingerprint);
        $this->assertFalse($this->api->canSign());
        $exceptionThrown = false;
        try {
            $this->api->createArchive(1);
        } catch (ConfigurationException $e) {
            $exceptionThrown = true;
            $this->assertConfigurationException('gpg/fingerprint',
                    $fingerprint,
                    'Could not use this signing key. Is it imported in the keyring and has no passphrase?', $e);
        }
        $this->assertTrue($exceptionThrown);
    }

 
    public function testCreateArchiveNoRequest() {
        $exceptionThrown = false;
        try {
            $this->api->createArchive(1);
        } catch (RecordNotFoundException $e) {
            $exceptionThrown = true;
            $this->assertRecordNotFoundException(ArchiveRequest::class,
                    ['id' => 1], $e);
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testCreateArchiveProcessing() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $request->setProcessing(true);
        $this->dao->write($request);
        $this->dao->commit();
        $this->assertEquals(false,
                $this->api->createArchive($request->getId()));
        $requests = $this->dao->search(ArchiveRequest::class);
        $this->assertEquals(1, count($requests));
        $this->assertEquals($request->getId(), $requests[0]->getId());
    }

    public function testCreateArchiveEmpty() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $archive = $this->api->createArchive($request->getId());
        $now = new \DateTime();
        $this->assertTrue($archive->checkSignature(null));
        // Unsign into $zipContent
        $gpg = new \gnupg();
        $zipContent = null;
        $signInfo = $gpg->verify($archive->getContent(), false, $zipContent);
        $this->assertNotEquals(false, $signInfo);
        $this->assertEquals($this->api->getFingerprint(),
                $signInfo[0]['fingerprint']);
        // Copy $zipContent to a file to be able to unzip it
        $tmpFilename = tempnam(sys_get_temp_dir(), 'archive');
        $tmpFile = fopen($tmpFilename, 'wb');
        fwrite($tmpFile, $zipContent);
        fclose($tmpFile);
        $zip = new \ZipArchive();
        $zip->open($tmpFilename);
        unlink($tmpFilename);
        // Check zip file contents
        $this->assertEquals(1, $zip->numFiles);
        $this->assertEquals('archive.txt', $zip->getNameIndex(0));
        $archInfo = $zip->getFromName('archive.txt');
        $archInfo = json_decode($archInfo, true);
        $zip->close();
        $this->assertNotNull($archInfo);
        $this->assertEquals($this->api->getAccount(), $archInfo['account']);
        $this->assertEquals($start->format('Y-m-d H:i:s'),
                $archInfo['dateStart']);
        $this->assertEquals($stop->format('Y-m-d H:i:s'),
                $archInfo['dateStop']);
        $generated = \DateTime::createFromFormat('Y-m-d H:i:s',
                $archInfo['generated']);
        $this->assertEquals(1, $archInfo['number']);
        $this->assertLessThan(10,
                abs($now->getTimestamp() - $generated->getTimestamp()));
    }

    /** @depends testCreateArchiveEmpty */
    public function testCreateArchiveSimple() {
        // Init fiscal tickets
        $prevTkt = null;
        for ($i = 1; $i <= 5; $i++) {
            $min = floor($i / 60);
            $sec = $i % 60;
            $date = \DateTime::createFromFormat('Y-m-d H:i:s',
                    sprintf("2018-01-18 10:%02d:%02d", $min, $sec));
            $ft = $this->createFiscalTicket($date, '0001', $i,
                    sprintf('Ticket %d', $i), $prevTkt);
            $this->dao->write($ft);
            $prevTkt = $ft;
        }
        $ft = $this->createZFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                        '2018-01-18 12:05:00'),
                '0001', 1, 'Session 1', null);
        $this->dao->write($ft);
        $cft = $this->createCustomFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                        '2018-01-18 12:10:00'),
                '0001', 1, 'Custom ticket 1', null);
        $this->dao->write($cft);
        $this->dao->commit();
        $start = new \DateTime('2018-01-01 00:00:00');
        $stop = new \DateTime('2018-01-20 00:00:00');
        $request = $this->api->addRequest($start, $stop);
        $archive = $this->api->createArchive($request->getId());
        $now = new \DateTime();
        $this->assertTrue($archive->checkSignature(null));
        // Unsign into $zipContent
        $gpg = new \gnupg();
        $zipContent = null;
        $signInfo = $gpg->verify($archive->getContent(), false, $zipContent);
        $this->assertNotEquals(false, $signInfo);
        $this->assertEquals($this->api->getFingerprint(),
                $signInfo[0]['fingerprint']);
        // Copy $zipContent to a file to be able to unzip it
        $tmpFilename = tempnam(sys_get_temp_dir(), 'archive');
        $tmpFile = fopen($tmpFilename, 'wb');
        fwrite($tmpFile, $zipContent);
        fclose($tmpFile);
        $zip = new \ZipArchive();
        $zip->open($tmpFilename);
        unlink($tmpFilename);
        // Check zip file contents
        $this->assertEquals(4, $zip->numFiles);
        $archInfo = $zip->getFromName('archive.txt');
        $this->assertNotEquals(false, $archInfo);
        $tktSeq = $zip->getFromName('tkt-0001-1.txt');
        $this->assertNotEquals(false, $tktSeq);
        $zSeq = $zip->getFromName('z-0001-1.txt');
        $this->assertNotEquals(false, $zSeq);
        $cSeq = $zip->getFromName('custom type-0001-1.txt');
        $this->assertNotEquals(false, $cSeq);
        $archInfo = json_decode($archInfo, true);
        $tkts = json_decode($tktSeq, true);
        $zs = json_decode($zSeq, true);
        $customs = json_decode($cSeq, true);
        $this->assertNotNull($archInfo);
        $this->assertNotNull($tkts);
        $this->assertNotNull($zs);
        $this->assertNotNull($customs);
        $zip->close();
        $generated = \DateTime::createFromFormat('Y-m-d H:i:s',
                $archInfo['generated']);
        $this->assertEquals($archive->getNumber(), $archInfo['number']);
        $this->assertLessThan(10,
                abs($now->getTimestamp() - $generated->getTimestamp()));
        $this->assertEquals($this->api->getAccount(), $archInfo['account']);
        $this->assertEquals($start->format('Y-m-d H:i:s'),
                $archInfo['dateStart']);
        $this->assertEquals($stop->format('Y-m-d H:i:s'),
                $archInfo['dateStop']);
        $generated = \DateTime::createFromFormat('Y-m-d H:i:s',
                $archInfo['generated']);
        $this->assertEquals(5, count($tkts));
        $this->assertEquals(1, count($zs));
        for ($i = 0; $i < 5; $i++) {
            $tkt = $tkts[$i];
            $this->assertEquals('tkt', $tkt['type']);
            $this->assertEquals('0001', $tkt['sequence']);
            $this->assertEquals($i + 1, $tkt['number']);
            $this->assertEquals(sprintf('2018-01-18 10:00:0%d', $i + 1),
                    $tkt['date']);
            $this->assertEquals(sprintf('Ticket %d', $i + 1), $tkt['content']);
            $this->assertEquals(true, $tkt['signature_ok']);
        }
        $this->assertEquals('z', $zs[0]['type']);
        $this->assertEquals('0001', $zs[0]['sequence']);
        $this->assertEquals(1, $zs[0]['number']);
        $this->assertEquals('2018-01-18 12:05:00', $zs[0]['date']);
        $this->assertEquals('Session 1', $zs[0]['content']);
        $this->assertEquals(true, $zs[0]['signature_ok']);
        $this->assertEquals('custom type', $customs[0]['type']);
        $this->assertEquals('0001', $customs[0]['sequence']);
        $this->assertEquals(1, $customs[0]['number']);
        $this->assertEquals('2018-01-18 12:10:00', $customs[0]['date']);
        $this->assertEquals('Custom ticket 1', $customs[0]['content']);
        $this->assertEquals(true, $customs[0]['signature_ok']);
    }

    /** @depends testCreateArchiveSimple */
    public function testCreateArchiveBadSignature() {
        // Init fiscal tickets
        $prevTkt = null;
        for ($i = 1; $i <= 5; $i++) {
            $min = floor($i / 60);
            $sec = $i % 60;
            $date = \DateTime::createFromFormat('Y-m-d H:i:s',
                    sprintf("2018-01-18 10:%02d:%02d", $min, $sec));
            $ft = $this->createFiscalTicket($date, '0001', $i,
                    sprintf('Ticket %d', $i), null);
            $this->dao->write($ft);
        }
        $ft = $this->createZFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                        '2018-01-18 12:05:00'),
                '0001', 1, 'Session 1', null);
        $this->dao->write($ft);
        $this->dao->commit();
        $start = new \DateTime('2018-01-01 00:00:00');
        $stop = new \DateTime('2018-01-20 00:00:00');
        $request = $this->api->addRequest($start, $stop);
        $archive = $this->api->createArchive($request->getId());
        $now = new \DateTime();
        $this->assertTrue($archive->checkSignature(null));
        // Unsign into $zipContent
        $gpg = new \gnupg();
        $zipContent = null;
        $signInfo = $gpg->verify($archive->getContent(), false, $zipContent);
        $this->assertNotEquals(false, $signInfo);
        $this->assertEquals($this->api->getFingerprint(),
                $signInfo[0]['fingerprint']);
        // Copy $zipContent to a file to be able to unzip it
        $tmpFilename = tempnam(sys_get_temp_dir(), 'archive');
        $tmpFile = fopen($tmpFilename, 'wb');
        fwrite($tmpFile, $zipContent);
        fclose($tmpFile);
        $zip = new \ZipArchive();
        $zip->open($tmpFilename);
        unlink($tmpFilename);
        // Check zip file contents
        $this->assertEquals(3, $zip->numFiles);
        $archInfo = $zip->getFromName('archive.txt');
        $this->assertNotEquals(false, $archInfo);
        $tktSeq = $zip->getFromName('tkt-0001-1.txt');
        $this->assertNotEquals(false, $tktSeq);
        $zSeq = $zip->getFromName('z-0001-1.txt');
        $this->assertNotEquals(false, $zSeq);
        $archInfo = json_decode($archInfo, true);
        $tkts = json_decode($tktSeq, true);
        $zs = json_decode($zSeq, true);
        $this->assertNotNull($archInfo);
        $this->assertNotNull($tkts);
        $this->assertNotNull($zs);
        $zip->close();
        $generated = \DateTime::createFromFormat('Y-m-d H:i:s',
                $archInfo['generated']);
        $this->assertEquals($archive->getNumber(), $archInfo['number']);
        $this->assertLessThan(10,
                abs($now->getTimestamp() - $generated->getTimestamp()));
        $this->assertEquals($this->api->getAccount(), $archInfo['account']);
        $this->assertEquals($start->format('Y-m-d H:i:s'),
                $archInfo['dateStart']);
        $this->assertEquals($stop->format('Y-m-d H:i:s'),
                $archInfo['dateStop']);
        $this->assertEquals(5, count($tkts));
        $this->assertEquals(1, count($zs));
        for ($i = 0; $i < 5; $i++) {
            $tkt = $tkts[$i];
            $this->assertEquals('tkt', $tkt['type']);
            $this->assertEquals('0001', $tkt['sequence']);
            $this->assertEquals($i + 1, $tkt['number']);
            $this->assertEquals(sprintf('2018-01-18 10:00:0%d', $i + 1),
                    $tkt['date']);
            $this->assertEquals(sprintf('Ticket %d', $i + 1), $tkt['content']);
            $this->assertEquals(($i == 0), $tkt['signature_ok']);
        }
        $this->assertEquals('z', $zs[0]['type']);
        $this->assertEquals('0001', $zs[0]['sequence']);
        $this->assertEquals(1, $zs[0]['number']);
        $this->assertEquals('2018-01-18 12:05:00', $zs[0]['date']);
        $this->assertEquals('Session 1', $zs[0]['content']);
        $this->assertEquals(true, $zs[0]['signature_ok']);
    }

    /** @depends testCreateArchiveEmpty */
    public function testCreateArchiveMultipage() {
        // Sequence 0001: 2 pages of tickets, a ticket before the archive
        //                and one after
        //                2 sessions, one before the archive
        // Sequence 0002: 2 pages of tickets, one session
        // Sequenc 0001 before archive
        $firstFt = $this->createFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                    sprintf('2017-12-20 16:00:00')),
                '0001', 1, 'Ticket 1', null);
        $this->dao->write($firstFt);
        // Sequence 0001 tkt page 1&2
        $prevTkt = $firstFt;
        for ($i = 2; $i <= ArchiveAPI::BATCH_SIZE + 5; $i++) {
            $min = floor($i / 60);
            $sec = $i % 60;
            $date = \DateTime::createFromFormat('Y-m-d H:i:s',
                    sprintf('2018-01-18 10:%02d:%02d', $min, $sec));
            $ft = $this->createFiscalTicket($date, '0001', $i,
                    sprintf('Ticket %d', $i), $prevTkt);
            $this->dao->write($ft);
            $prevTkt = $ft;
        }
        // Sequence 0001 tkt after archive
        $ft = $this->createFiscalTicket(
                 \DateTime::createFromFormat('Y-m-d H:i:s',
                    sprintf('2018-03-20 16:00:00')),
                '0001', $prevTkt->getNumber() + 1,
                sprintf('Ticket %d', $prevTkt->getNumber() + 1), $prevTkt);
        $this->dao->write($ft);
        // Sequence 0002 tkt pages 1&2
        $prevTkt = null;
        for ($i = 1; $i <= ArchiveAPI::BATCH_SIZE + 3; $i++) {
            $min = floor($i / 60);
            $sec = $i % 60;
            $date = \DateTime::createFromFormat('Y-m-d H:i:s',
                    sprintf('2018-01-12 14:%02d:%02d', $min, $sec));
            $ft = $this->createFiscalTicket($date, '0002', $i,
                    sprintf('Ticket %d', $i), $prevTkt);
            $this->dao->write($ft);
            $prevTkt = $ft;
        }
        // Sequence 0001 session before archive and in archive
        $firstZFt = $this->createZFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                        '2017-12-20 18:05:00'),
                '0001', 1, 'Session 1', null);
        $this->dao->write($firstZFt);
        $ft = $this->createZFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                        '2018-01-18 12:05:00'),
                '0001', 2, 'Session 2', $firstZFt);
        $this->dao->write($ft);
        // Sequence 0002 session in archive
        $ft = $this->createZFiscalTicket(
                \DateTime::createFromFormat('Y-m-d H:i:s',
                        '2018-01-12 16:05:00'),
                '0002', 1, 'Session 1', null);
        $this->dao->write($ft);
        $this->dao->commit();
        // Create archive
        $start = new \DateTime('2018-01-01 00:00:00');
        $stop = new \DateTime('2018-01-20 00:00:00');
        $request = $this->api->addRequest($start, $stop);
        $archive = $this->api->createArchive($request->getId());
        $now = new \DateTime();
        $this->assertTrue($archive->checkSignature(null));
        // Unsign into $zipContent
        $gpg = new \gnupg();
        $zipContent = null;
        $signInfo = $gpg->verify($archive->getContent(), false, $zipContent);
        $this->assertNotEquals(false, $signInfo);
        $this->assertEquals($this->api->getFingerprint(),
                $signInfo[0]['fingerprint']);
        // Copy $zipContent to a file to be able to unzip it
        $tmpFilename = tempnam(sys_get_temp_dir(), 'archive');
        $tmpFile = fopen($tmpFilename, 'wb');
        fwrite($tmpFile, $zipContent);
        fclose($tmpFile);
        $zip = new \ZipArchive();
        $zip->open($tmpFilename);
        unlink($tmpFilename);
        // Check zip file contents
        $this->assertEquals(7, $zip->numFiles);
        $archInfo = $zip->getFromName('archive.txt');
        $this->assertNotEquals(false, $archInfo);
        // Check files presence
        $tktSeq1 = $zip->getFromName('tkt-0001-1.txt');
        $tktSeq2 = $zip->getFromName('tkt-0001-2.txt');
        $tktSeq3 = $zip->getFromName('tkt-0002-1.txt');
        $tktSeq4 = $zip->getFromName('tkt-0002-2.txt');
        $this->assertNotEquals(false, $tktSeq1);
        $this->assertNotEquals(false, $tktSeq2);
        $this->assertNotEquals(false, $tktSeq3);
        $this->assertNotEquals(false, $tktSeq4);
        $zSeq1 = $zip->getFromName('z-0001-1.txt');
        $zSeq2 = $zip->getFromName('z-0002-1.txt');
        $this->assertNotEquals(false, $zSeq1);
        $this->assertNotEquals(false, $zSeq2);
        $archInfo = json_decode($archInfo, true);
        $tkts1 = json_decode($tktSeq1, true);
        $tkts2 = json_decode($tktSeq2, true);
        $tkts3 = json_decode($tktSeq3, true);
        $tkts4 = json_decode($tktSeq4, true);
        $zs1 = json_decode($zSeq1, true);
        $zs2 = json_decode($zSeq2, true);
        $this->assertNotNull($archInfo);
        $this->assertNotNull($tkts1);
        $this->assertNotNull($tkts2);
        $this->assertNotNull($tkts3);
        $this->assertNotNull($tkts4);
        $this->assertNotNull($zs1);
        $this->assertNotNull($zs2);
        $zip->close();
        // Check meta
        $this->assertEquals($archive->getNumber(), $archInfo['number']);
        $generated = \DateTime::createFromFormat('Y-m-d H:i:s',
                $archInfo['generated']);
        $this->assertLessThan(10,
                abs($now->getTimestamp() - $generated->getTimestamp()));
        $this->assertEquals($this->api->getAccount(), $archInfo['account']);
        $this->assertEquals($start->format('Y-m-d H:i:s'),
                $archInfo['dateStart']);
        $this->assertEquals($stop->format('Y-m-d H:i:s'),
                $archInfo['dateStop']);
        // Check tkts
        $this->assertEquals(ArchiveAPI::BATCH_SIZE, count($tkts1));
        for ($i = 0; $i < ArchiveAPI::BATCH_SIZE; $i++) {
            $min = floor(($i + 2) / 60);
            $sec = ($i + 2) % 60;
            $tkt = $tkts1[$i];
            $this->assertEquals('tkt', $tkt['type']);
            $this->assertEquals('0001', $tkt['sequence']);
            $this->assertEquals($i + 2, $tkt['number']);
            $this->assertEquals(sprintf('2018-01-18 10:%02d:%02d', $min, $sec),
                    $tkt['date']);
            $this->assertEquals(sprintf('Ticket %d', $i + 2), $tkt['content']);
            $this->assertEquals(true, $tkt['signature_ok']);
        }
        $this->assertEquals(4, count($tkts2));
        for ($i = 0; $i < 4; $i++) {
            $min = floor(($i + ArchiveAPI::BATCH_SIZE + 2) / 60);
            $sec = ($i + ArchiveAPI::BATCH_SIZE + 2) % 60;
            $tkt = $tkts2[$i];
            $num = ArchiveAPI::BATCH_SIZE + $i + 2;
            $this->assertEquals('tkt', $tkt['type']);
            $this->assertEquals('0001', $tkt['sequence']);
            $this->assertEquals($num, $tkt['number']);
            $this->assertEquals(sprintf('2018-01-18 10:%02d:%02d', $min, $sec),
                    $tkt['date']);
            $this->assertEquals(sprintf('Ticket %d', $num), $tkt['content']);
            $this->assertEquals(true, $tkt['signature_ok']);
        }
        $this->assertEquals(ArchiveAPI::BATCH_SIZE, count($tkts3));
        for ($i = 0; $i < ArchiveAPI::BATCH_SIZE; $i++) {
            $min = floor(($i + 1) / 60);
            $sec = ($i + 1) % 60;
            $tkt = $tkts3[$i];
            $this->assertEquals('tkt', $tkt['type']);
            $this->assertEquals('0002', $tkt['sequence']);
            $this->assertEquals($i + 1, $tkt['number']);
            $this->assertEquals(sprintf('2018-01-12 14:%02d:%02d', $min, $sec),
                    $tkt['date']);
            $this->assertEquals(sprintf('Ticket %d', $i + 1), $tkt['content']);
            $this->assertEquals(true, $tkt['signature_ok']);
        }
        $this->assertEquals(3, count($tkts4));
        for ($i = 0; $i < 3; $i++) {
            $min = floor(($i + ArchiveAPI::BATCH_SIZE + 1) / 60);
            $sec = ($i + ArchiveAPI::BATCH_SIZE + 1) % 60;
            $tkt = $tkts4[$i];
            $num = ArchiveAPI::BATCH_SIZE + $i + 1;
            $this->assertEquals('tkt', $tkt['type']);
            $this->assertEquals('0002', $tkt['sequence']);
            $this->assertEquals($num, $tkt['number']);
            $this->assertEquals(sprintf('2018-01-12 14:%02d:%02d', $min, $sec),
                    $tkt['date']);
            $this->assertEquals(sprintf('Ticket %d', $num), $tkt['content']);
            $this->assertEquals(true, $tkt['signature_ok']);
        }
        // Check z 
        $this->assertEquals(1, count($zs1));
        $this->assertEquals('z', $zs1[0]['type']);
        $this->assertEquals('0001', $zs1[0]['sequence']);
        $this->assertEquals(2, $zs1[0]['number']);
        $this->assertEquals('2018-01-18 12:05:00', $zs1[0]['date']);
        $this->assertEquals('Session 2', $zs1[0]['content']);
        $this->assertEquals(true, $zs1[0]['signature_ok']);
        $this->assertEquals(1, count($zs2));
        $this->assertEquals('z', $zs2[0]['type']);
        $this->assertEquals('0002', $zs2[0]['sequence']);
        $this->assertEquals(1, $zs2[0]['number']);
        $this->assertEquals('2018-01-12 16:05:00', $zs2[0]['date']);
        $this->assertEquals('Session 1', $zs2[0]['content']);
        $this->assertEquals(true, $zs2[0]['signature_ok']);
    }
}
