<?php

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;
use \Pasteque\Server\System\DAO\DAOCondition;

function fiscalLogin($app, $ptApp, $request, $response) {
    $data = $request->getParsedBody();
    if (!empty($data['user']) && !empty($data['password'])) {
        $apiResult = APICaller::run($ptApp, 'login', 'login',
                ['login' => $data['user'], 'password' => $data['password']]);
        if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
            // Set the cookie
            $basePath = $request->getUri()->getBasePath();
            if ($basePath == '') {
                $basePath = '/';
            }
            $newToken = $apiResult->getContent();
            if ($newToken != null) {
                $response = $response->withHeader(Login::TOKEN_HEADER, $newToken);
                $cookie = SetCookie::create(Login::TOKEN_HEADER)
                        ->withValue($newToken)
                        ->withMaxAge($ptApp->getJwtTimeout())
                        ->withPath($basePath)
                        ->withDomain($request->getUri()->getHost());
                $response = FigResponseCookies::set($response, $cookie);
                $user = $ptApp->getIdentModule()->getUser($data['user']);
                $ptApp->login($user);
            }
        }
    }
    return $response;
}
function fiscalTpl($ptApp, $response, $template, $data = []) {
    $body = $response->getBody();
    $body->write(file_get_contents(__DIR__ . '/../templates/header.html'));
    require_once(__DIR__ . '/../templates/' . $template);
    $content = render($ptApp, $data);
    $body->write($content);
    $body->write(file_get_contents(__DIR__ . '/../templates/footer.html'));
}

$app->any('/fiscal/z/{sequence}',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    } else {
        fiscalTpl($ptApp, $response, 'z.html');
        return $response;
    }
});

/** Z ticket listing */
$app->any('/fiscal/sequence/{sequence}/z/',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    } else {
        // Get pagination input
        $queryParams = $request->getQueryParams();
        $page = (!empty($queryParams['page'])) ? $queryParams['page'] : 0;
        $sequence = $args['sequence'];
        $count = 10;
        // Get total count of Z tickets (for pagination)
        $apiCount = APICaller::run($ptApp, 'fiscal', 'countZ',
                ['sequence' => $sequence]);
        $pageCount = 0;
        if ($apiCount->getStatus() != APIResult::STATUS_CALL_OK) {
            fiscalTpl($ptApp, $response, 'apierror.php', $apiCount);
            return $response;
        } else {
            $zCount = $apiCount->getContent();
            $pageCount = intval($apiCount->getContent() / $count);
            if ($zCount % $count > 0) { $pageCount++; }
        }
        // Get Z tickets
        $apiResult = APICaller::run($ptApp, 'fiscal', 'listZ',
                ['sequence' => $sequence, 'count' => $count, 'page' => $page]);
        if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
            // Convert tickets to structs and check signature
            $data = $apiResult->getContent();
            $structs = [];
            if ($page == 0 && count($data) > 0) {
                $struct = $data[0]->toStruct();
                $struct['signature_status'] = $data[0]->checkSignature(null) ? 'ok' : 'NOK';
                $structs[] = $struct;
            }
            for ($i = 1; $i < count($data); $i++) {
                $struct = $data[$i]->toStruct();
                $struct['signature_status'] = $data[$i]->checkSignature($data[$i - 1]) ? 'ok' : 'NOK';
                $structs[] = $struct;
            }
            // Render
            fiscalTpl($ptApp, $response, 'listtkts.php', ['sequence' => $sequence,
                            'page' => $page, 'pageCount' => $pageCount,
                            'tickets' => $structs, 'typeName' => 'tickets Z']);
        } else {
            fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
        }
        return $response;
    }
});

/** Tickets listing */
$app->any('/fiscal/sequence/{sequence}/tickets/',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    } else {
        // Get pagination input
        $queryParams = $request->getQueryParams();
        $page = (!empty($queryParams['page'])) ? $queryParams['page'] : 0;
        $sequence = $args['sequence'];
        $count = 10;
        // Get total count of tickets (for pagination)
        $apiCount = APICaller::run($ptApp, 'fiscal', 'countTickets',
                ['sequence' => $sequence]);
        $pageCount = 0;
        if ($apiCount->getStatus() != APIResult::STATUS_CALL_OK) {
            fiscalTpl($ptApp, $response, 'apierror.php', $apiCount);
            return $response;
        } else {
            $zCount = $apiCount->getContent();
            $pageCount = intval($apiCount->getContent() / $count);
            if ($zCount % $count > 0) { $pageCount++; }
        }
        // Get tickets
        $apiResult = APICaller::run($ptApp, 'fiscal', 'listTickets',
                ['sequence' => $sequence, 'count' => $count, 'page' => $page]);
        if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
            // Convert tickets to structs and check signature
            $data = $apiResult->getContent();
            $structs = [];
            if ($page == 0 && count($data) > 0) {
                $struct = $data[0]->toStruct();
                $struct['signature_status'] = $data[0]->checkSignature(null) ? 'ok' : 'NOK';
                $structs[] = $struct;
            }
            for ($i = 1; $i < count($data); $i++) {
                $struct = $data[$i]->toStruct();
                $struct['signature_status'] = $data[$i]->checkSignature($data[$i - 1]) ? 'ok' : 'NOK';
                $structs[] = $struct;
            }
            // Render
            fiscalTpl($ptApp, $response, 'listtkts.php', ['sequence' => $sequence,
                            'page' => $page, 'pageCount' => $pageCount,
                            'tickets' => $structs, 'typeName' => 'tickets']);
        } else {
            fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
        }
        return $response;
    }
});

/** Other listing */
$app->any('/fiscal/sequence/{sequence}/other',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    }
    // Check type
    $types = null;
    $apiTypeRes = APICaller::run($ptApp, 'fiscal', 'getTypes');
    if ($apiTypeRes->getStatus() == APIResult::STATUS_CALL_OK) {
        $types = $apiTypeRes->getContent();
    } else {
        fiscalTpl($ptApp, $response, 'apierror.php', $apiTypeRes);
        return $response;
    }
    $validType = false;
    $queryParams = $request->getQueryParams();
    $type = $queryParams['type'];
    foreach ($types as $t) {
        if ($t == $type) {
            $validType = true;
            break;
        }
    }
    if (!$validType) {
        return fiscalHomePage();
    }
    // Get pagination input
    $page = (!empty($queryParams['page'])) ? $queryParams['page'] : 0;
    $sequence = $args['sequence'];
    $count = 10;
    // Get total count of tickets (for pagination)
    $apiCount = APICaller::run($ptApp, 'fiscal', 'count', [$sequence, $type]);
    $pageCount = 0;
    if ($apiCount->getStatus() != APIResult::STATUS_CALL_OK) {
        fiscalTpl($ptApp, $response, 'apierror.php', $apiCount);
        return $response;
    } else {
        $ticketCount = $apiCount->getContent();
        $pageCount = intval($apiCount->getContent() / $count);
        if ($ticketCount % $count > 0) { $pageCount++; }
    }
    // Get tickets
    $apiResult = APICaller::run($ptApp, 'fiscal', 'listByType',
            [$type, $sequence, $count, $page]);
    if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
        // Convert tickets to structs and check signature
        $data = $apiResult->getContent();
        $structs = [];
        if ($page == 0 && count($data) > 0) {
            $struct = $data[0]->toStruct();
            $struct['signature_status'] = $data[0]->checkSignature(null) ? 'ok' : 'NOK';
            $structs[] = $struct;
        }
        for ($i = 1; $i < count($data); $i++) {
            $struct = $data[$i]->toStruct();
            $struct['signature_status'] = $data[$i]->checkSignature($data[$i - 1]) ? 'ok' : 'NOK';
            $structs[] = $struct;
        }
        // Render
        $typeName = sprintf('%ss', $type);
        fiscalTpl($ptApp, $response, 'listtkts.php', ['sequence' => $sequence,
                        'page' => $page, 'pageCount' => $pageCount,
                        'tickets' => $structs, 'typeName' => $typeName]);
    } else {
        fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
    }
    return $response;
});


function fiscalHomePage($ptApp, $response) {
    $data = ['user' => $ptApp->getCurrentUser()['id']];
    $apiResult = APICaller::run($ptApp, 'fiscal', 'getSequences');
    if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
        $data['sequences'] = $apiResult->getContent();
    } else {
        fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
        return $response;
    }
    $apiTypeRes = APICaller::run($ptApp, 'fiscal', 'getTypes');
    if ($apiTypeRes->getStatus() == APIResult::STATUS_CALL_OK) {
        $data['types'] = $apiTypeRes->getContent();
    } else {
        fiscalTpl($ptApp, $response, 'apierror.php', $apiTypeRes);
        return $response;
    }
    if ($ptApp->isGPGEnabled()) {
        $data['gpg'] = true;
        $apiResult = APICaller::run($ptApp, 'archive', 'listArchives');
        if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
            $data['archives'] = $apiResult->getContent();
        } else {
            fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
            return $response;
        }
        $apiResult = APICaller::run($ptApp, 'archive', 'getRequests');
        if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
            $data['archiverequests'] = $apiResult->getContent();
        } else {
            fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
            return $response;
        }
    }
    fiscalTpl($ptApp, $response, 'menu.php', $data);
    return $response;
}

/** Fiscal home page */
$app->any('/fiscal/',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    } else {
        return fiscalHomePage($ptApp, $response);
    }
});

$app->any('/fiscal/disconnect',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    fiscalTpl($ptApp, $response, 'login.php');
    return $response; // See login middleware for the auth destruction
});

$app->POST('/fiscal/createarchive',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    }
    if (!$ptApp->isGPGEnabled()) {
        return $response->withStatus(403);
    }
    $queryParams = $request->getParsedBody();
    $dateStart = DateUtils::readDate($queryParams['dateStart']);
    $dateStop = DateUtils::readDate($queryParams['dateStop']);
    $apiResult = APICaller::run($ptApp, 'archive', 'addRequest',
            [$dateStart, $dateStop]);
    if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
        return $response->withRedirect('../fiscal/');
    } else {
        return fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
    }
});

/** Download an archive */
$app->GET('/fiscal/archive/{number}',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    }
    $apiRes = APICaller::run($ptApp, 'archive', 'getArchive', $args['number']);
    if ($apiRes->getStatus() == APIResult::STATUS_CALL_OK) {
        $archive = $apiRes->getContent();
        if ($archive == null) {
            return $response->withStatus(404);
        }
        $content = $archive->getContent();
        $contentLength = null;
        switch (gettype($content)) {
            case 'resource':
                $contentLength = filesize($content);
                break;
            default:
                $contentLength = strlen($content);
        }
        $response = $response->withHeader('content-type',
                'application/pgp-encrypted');
        $response = $response->withHeader('content-length', $contentLength);
        $response = $response->withHeader('content-disposition',
                sprintf('attachment; filename="archive_pasteque_%d.gpg"',
                        $archive->getNumber()));
        $body = $response->getBody();
        switch (gettype($content)) {
            case 'resource':
                while (!feof($content)) {
                    $body->write(fread($content, 20480));
                }
                break;
            default:
                $body->write($content);
        }
        return $response;
    } else {
        fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
        return $response;
    }
});

/** Fiscal export */
$app->GET('/fiscal/export',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
        return $response;
    } else {
        $params = $request->getQueryParams();
        $interval = null;
        $from = null;
        $to = new DateTime();
        if (!empty($params['period'])) {
            try {
                $interval = new DateInterval($params['period']);
                $from = clone $to;
                $from->sub($interval);
            } catch (Exception $e) {
                return $response->withStatus(400, 'Bad Request');
            }
        } elseif (!empty($params['from'])) {
            $from = DateUtils::readDate($params['from']);
            if ($from === false) {
                return $response->withStatus(400, 'Bad Request');
            }
            if (!empty($params['to'])) {
                $to = DateUtils::readDate($params['to']);
                if ($to === false) {
                    return $response->withStatus(400, 'Bad Request');
                }
            }
        }
        $jsonFileName = tempnam(sys_get_temp_dir(), 'pasteque_fiscal_export_');
        $zipFileName = tempnam(sys_get_temp_dir(), 'pasteque_fiscal_export_');
        try {
            // Create tmp file output
            $exportName = '';
            if ($from !== null) {
                $exportName = sprintf('fiscal_export-%s-%s', $from->format('Ymd_Hi'), $to->format('Ymd_Hi'));
            } else {
                $exportName = sprintf('fiscal_export-%s', $to->format('Ymd_Hi'));
            }
            $file = fopen($jsonFileName, 'w');
            fwrite($file, '['); // Init json string
            // Get all fiscal tickets
            // Run by batches to limit memory consumption
            $batchSize = 100;
            $page = 0;
            $done = false;
            $found = false;
            $searchConds = null;
            if ($from !== null) {
                $searchConds = [new DAOCondition('date', '>=', $from),
                        new DAOCondition('date', '<=', $to)];
            }
            while (!$done) {
                $apiResult = APICaller::run($ptApp, 'fiscal', 'search', [$searchConds, $batchSize, $batchSize * $page, ['type', 'sequence', 'number']]);
                $page++;
                if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
                    // Convert tickets to struct
                    $data = $apiResult->getContent();
                    if (count($data) == 0) {
                        $done = true;
                    } else {
                        $found = true;
                        $ftkts = [];
                        for ($i = 0; $i < count($data); $i++) {
                            $ftkts[] = $data[$i]->toStruct();
                        }
                        $strData = json_encode($ftkts);
                        // Remove enclosing '[' and ']' before appending and add ',' for next the record
                        $strData = substr($strData, 1, -1) . ',';
                        fwrite($file, $strData);
                    }
                } else {
                    fclose($file);
                    unlink($jsonFileName);
                    unlink($zipFileName);
                    fiscalTpl($ptApp, $response, 'apierror.php', $apiResult);
                    return $response;
                }
            }
            // End json string and close file
            if ($found) {
                fseek($file, -1, SEEK_END);
            }
            fwrite($file, "]\n");
            fclose($file);
            // Compress the file
            $zip = new ZipArchive();
            $zip->open($zipFileName, ZipArchive::CREATE);
            $zip->addFile($jsonFileName, sprintf('%s.txt', $exportName));
            $zip->close();
            $response = $response->withHeader('content-type', 'application/zip');
            $response = $response->withHeader('content-disposition', sprintf('attachment; filename="%s.zip"', $exportName));
            $zipFile = fopen($zipFileName, 'r+');
            $body = $response->getBody();
            while (!feof($zipFile)) {
                $body->write(fread($zipFile, 20480));
            }
            unlink($jsonFileName);
            unlink($zipFileName);
            return $response;
        } catch (Exception $e) {
            // Clean temp files on error
            if (file_exists($jsonFileName)) {
                unlink($jsonFileName);
            }
            if (file_exists($zipFileName)) {
                unlink($zipFileName);
            }
            throw $e;
        }
    }
});

/** Fiscal export API */
$app->GET('/api/fiscal/export',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $queryParams = $request->getQueryParams();
    $dateStart = (empty($queryParams['dateStart'])) ? null
            : DateUtils::readDate($queryParams['dateStart']);
    $dateStop =  (empty($queryParams['dateStop'])) ? null
            : DateUtils::readDate($queryParams['dateStop']);
    if ($dateStart === false) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_INVALID_DATE,
                null, 'dateStart', null, $queryParams['dateStart']);
        return $response->reject($e, 'Invalid dateStart');
    }
    if ($dateStop === false) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_INVALID_DATE,
                null, 'dateStop', null, $queryParams['dateStop']);
        return $response->reject($e, 'Invalid dateStop');
    }
    if ($dateStart === null && $dateStop === null) {
        return $response->withAPIResult(APICaller::run($ptApp, 'fiscal',
                'getAll', [['type', 'sequence', 'number']]));
    } else {
        $conditions = [];
        if ($dateStart != null) {
            $conditions[] = new DAOCondition('date', '>=', $dateStart);
        }
        if ($dateStop != null) {
            $conditions[] = new DAOCondition('date', '<=', $dateStop);
        }
        return $response->withAPIResult(APICaller::run($ptApp, 'fiscal',
                'search', [$conditions, null, null,
                        ['type', 'sequence', 'number']]));
    }
});

function fiscal_importTickets($ptApp, $data) {
    $tkts = [];
    foreach ($data as $tkt) {
        $tkt['date'] = DateUtils::readDate($tkt['date']);
        unset($tkt['id']);
        $fTkt = null;
        if ($tkt['number'] === 0) {
            // Load EOS from database to update it
            $fTkt = FiscalTicket::loadFromId(['type' => $tkt['type'],
                    'sequence' => $tkt['sequence'],
                    'number' => $tkt['number']], $ptApp->getDao());
        }
        if ($fTkt == null) {
            // Create a new ticket if EOS is absent. For other tickets the API
            // will check agains snapshots anyway.
            $fTkt = new FiscalTicket();
        }
        try {
            $fTkt->merge($tkt, $ptApp->getDao());
        } catch (InvalidFieldException $e) {
            return APIResult::reject($e);
        }
        $tkts[] = $fTkt;
    }
    $apiResult = APICaller::run($ptApp, 'fiscal', 'batchImport', [$tkts]);
    return $apiResult;
}

/** Fiscal import API */
$app->POST('/api/fiscal/import',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    if (!$ptApp->isFiscalMirror()) {
        return $response->withStatus(400, 'Only available for Fiscal Mirrors');
    }
    $encoding = $request->getHeaderLine('Content-Encoding');
    if (!empty($encoding) && strtolower($encoding) == 'zip') {
        // Copy incoming zip file into tmp
        $zipFileName = tempnam(sys_get_temp_dir(), 'pasteque_zipimport');
        $body = $request->getBody();
        $file = fopen($zipFileName, 'w');
        $zipdata = $body->read(2048);
        while ($zipdata != '') {
            fwrite($file, $zipdata);
            $zipdata = $body->read(2048);
        }
        fclose($file);
        $zip = new ZipArchive();
        // Read and check zip content
        $res = $zip->open($zipFileName);
        if ($res !== true) {
            if (file_exists($zipFileName)) {
                unlink($zipFileName);
            }
            $result = APIResult::reject('Cannot open zip archive');
            return $response->withAPIResult($result);
        }
        if ($zip->count() != 1) {
            $zip->close();
            if (file_exists($zipFileName)) {
                unlink($zipFileName);
            }
            $result = APIResult::reject('Zip file must contain only one file');
            return $response->withAPIResult($result);
        }
        // Replace request body with the uncompressed content
        $newBody = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $newBody->write($zip->getFromIndex(0));
        $request = $request->withBody($newBody);
        $request = $request->withoutHeader('Content-Encoding');
        $request->reparseBody(); // refresh
        $zip->close();
        unlink($zipFileName);
    } elseif (!empty($encoding) && strtolower($encoding) != 'identity') {
        // Identity should not be set in request header, but accept it anyway
        $response = $response->withStatus(415, 'Unsupported Media Type');
        $response->getBody()->write(sprintf('Unsupported Content-Encoding "%s", must be "zip" or not set.', $encoding));
        return $response;
    }
    $data = $request->getParsedBody();
    return $response->withAPIResult(fiscal_importTickets($ptApp, $data));
});

/** Fiscal import interface */
$app->POST('/fiscal/import',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    if (!$ptApp->isFiscalMirror()) {
        return $response->withStatus(400, 'Only available for Fiscal Mirrors');
    }
    $files = $request->getUploadedFiles();
    if (empty($files['file'])) {
        return $response->withStatus(400, 'Bad Request');
    }
    $file = $files['file'];
    $stream = $file->getStream();
    $fileName = $stream->getMetadata('uri');
    // Try to unzip if required
    $zip = new ZipArchive();
    $res = $zip->open($fileName);
    if ($res === true) {
        if ($zip->count() != 1) {
            $apiResult = APIResult::reject("le fichier zip ne doit contenir qu'un seul fichier.");
            fiscalTpl($ptApp, $response, 'imported.php', $apiResult);
            return $response;
        }
        $data = $zip->getFromIndex(0);
        $zip->close();
    } else {
        $data = '';
        $read = $stream->read(2048);
        while ($read != '') {
            $data .= $read;
            $read = $stream->read(2048);
        }
    }
    $data = json_decode($data, true);
    if ($data === null) {
            $apiResult = APIResult::reject("les tickets fiscaux n'ont pu être lus depuis le fichier envoyé.");
            fiscalTpl($ptApp, $response, 'imported.php', $apiResult);
            return $response;
    }
    $apiResult = fiscal_importTickets($ptApp, $data);
    fiscalTPL($ptApp, $response, 'imported.php', $apiResult);
    return $response;
});

$app->GET('/fiscal/help/tickets',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
    } else {
        fiscalTpl($ptApp, $response, 'help_tickets.php');
    }
    return $response;
});

$app->GET('/fiscal/help/archives',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
    } else {
        return fiscalTpl($ptApp, $response, 'help_archives.php');
    }
    return $response;
});

$app->GET('/fiscal/help/issues',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $response = fiscalLogin($this, $ptApp, $request, $response);
    if ($ptApp->getCurrentUser() == null) {
        fiscalTpl($ptApp, $response, 'login.php');
    } else {
        return fiscalTpl($ptApp, $response, 'help_issues.php');
    }
    return $response;
});

