<?php
// Check the status of fiscal tickets

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\API\FiscalAPI;
use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\DAO\DAOCondition;

if ($argc < 2) {
    die("fiscalstatus.php <v8 user id> [sequence name] [type]\n"
            . "  Check fiscal tickets and find errors.\n"
            . "  It can be very resource intensive and should be ran on a copy of the database\n"
            . "  instead of within a production instance.\n");
}
$v8User = $argv[1];
$sequence = null;
$type = null;
if ($argc >= 3) {
    $sequence = $argv[2];
}
if ($argc >= 4) {
    $type = $argv[3];
}

$projectRoot = dirname(dirname(__DIR__));
require_once $projectRoot . '/vendor/autoload.php';

// Set v8
$cfgFile = $projectRoot . '/config/config.ini';
if (!is_readable($cfgFile)) {
    // Check for a moved configuration
    $envCfgFile = getenv('PT_CONFIG_' . preg_replace('/[^[:alnum:]]/', '_', $projectRoot));
    if (($envCfgFile === false) || !is_readable($envCfgFile)) {
        die('No config file found');
    }
    $cfgFile = $envCfgFile;
}

$ptApp = AppContext::loadFromConfig(parse_ini_file($cfgFile));
unset($cfgFile);

if ($ptApp->getDbModule()->getDatabase($v8User) === false) {
    die(sprintf("V8 user %s not found\n", $v8User));
}
$ptApp->login($ptApp->getIdentModule()->getUser($v8User));

$api = \Pasteque\Server\API\FiscalAPI::fromApp($ptApp);

// List all sequences and types
$allSequences = $api->getSequences();
if ($sequence !== null) {
    if (!in_array($sequence, $allSequences)) {
        printf("Sequence \"%s\" not found\n", $sequence);
        printf("Available sequences are:\n");
        foreach($allSequences as $seq) {
            printf("  %s\n", $seq);
        }
        die();
    }
}
$allTypes = $api->getTypes();
if ($type !== null) {
    if (!in_array($type, $allTypes)) {
        printf("Type \"%s\" not found\n", $type);
        printf("Available types are:\n");
        foreach($allTypes as $typ) {
            printf("  %s\n", $typ);
        }
        die();
    }
}

function printProgress($seq, $typ, $offset, $total) {
    printf("\rSequence %s %s [%d/%d]", $typ, $seq, $offset, $total);
}
function printResult($seq, $typ, $errs) {
    printf("\r                                                            \r");
    if ($errs === null) {
        printf("Sequence %s %s is empty\n", $typ, $seq);
    } elseif (count($errs) == 0) {
        printf("Sequence %s %s is clean\n", $typ, $seq);
    } else {
        printf("Sequence %s %s has %d errors\n", $typ, $seq, count($errs));
        foreach ($errs as $err) {
            printf("  Tkt %d: %s\n", $err['number'], $err['error']);
        }
    }
}

function checkSequence($seq, $type, $api) {
    $chunk = 250;
    $sequenceCond = [new DAOCondition('type', '=', $type),
            new DAOCondition('sequence', '=', $seq)];
    $ftCount = $api->count($seq, $type);
    if ($ftCount == 0) {
        return null;
    }
    $pages = $ftCount / $chunk;
    $errors = [];
    if ($ftCount % $chunk != 0) {
        $pages++;
    }
    $previousFTicket = null;
    $previousNumber = 0;
    for ($i = 0; $i < $pages; $i++) {
        printProgress($seq, $type, $i * $chunk, $ftCount);
        $tkts = $api->search($sequenceCond, $chunk, $i * $chunk + 1, 'number');
        foreach ($tkts as $tkt) {
            $continuous = true;
            if ($tkt->getNumber() != $previousNumber + 1) {
                for ($miss = $previousNumber +1; $miss < $tkt->getNumber(); $miss++) {
                    $errors[] = ['number' => $miss, 'error' => 'Missing'];
                }
                $continuous = false;
            }
            $signOk = $tkt->checkSignature($previousFTicket);
            if ($signOk && !$continuous) {
                $errors[] = ['number' => $tkt->getNumber(), 'error' => sprintf('Signed with number %d', $previousNumber)];
            }
            if (!$signOk && $continuous) {
                $errors[] = ['number' => $tkt->getNumber(), 'error' => 'Bad signature'];
            }
            $previousFTicket = $tkt;
            $previousNumber = $tkt->getNumber();
        }
    }
    $eos = $api->search([new DAOCondition('type', '=', $type),
            new DAOCondition('sequence', '=', $seq),
            new DAOCondition('number', '=', 0)], null, null, 'number');
    if (count($eos) == 0) {
        if ($ftCount != 0) {
            $errors[] = ['number' => 0, 'error' => 'Missing'];
        }
    } else {
        $eos = $eos[0];
        $signOk = $eos->checkSignature($previousFTicket);
        if (!$signOk) {
            $errors[] = ['number' => 0, 'error' => 'Bad EOS signature'];
        }
    }
    return $errors;
}

$sequences = $allSequences;
if ($sequence != null) {
    $sequences = [$sequence];
}
$types = $allTypes;
if ($type != null) {
    $types = [$type];
}

foreach ($sequences as $seq) {
    foreach ($types as $typ) {
        $errs = checkSequence($seq, $typ, $api);
        printResult($seq, $typ, $errs);
    }
}
