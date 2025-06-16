<?php
// Import script. Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\API\ResourceAPI;
use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\DAO\DAOCondition;

if ($argc < 2) {
    die("failures.php <v8 user id>\n"
            . "  List distinct tickets from the failure sequences.\n");
}
$v8User = $argv[1];

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

// List all sequences
$sequences = $api->getSequences();
$failSequences = [];
foreach ($sequences as $seq) {
    if (substr($seq, 0, 7) == 'failure') {
        $failSequences[] = $seq;
    }
}

function sameTicket($tkt1, $tkt2) {
    return ($tkt1['cashRegister']['reference'] == $tkt2['cashRegister']['reference']
            && $tkt1['sequence'] == $tkt2['sequence']
            && $tkt1['number'] == $tkt2['number']
            && $tkt1['date'] == $tkt2['date']
            && $tkt1['custCount'] == $tkt2['custCount']
            && $tkt1['price'] == $tkt2['price']
            && $tkt1['taxedPrice'] == $tkt2['taxedPrice']
            && $tkt1['finalPrice'] == $tkt2['finalPrice']
            && $tkt1['finalTaxedPrice'] == $tkt2['finalTaxedPrice']
            && $tkt1['user'] == $tkt2['user']
            && count($tkt1['lines']) == count($tkt2['lines']));
    // it should be suffiscient.
}

foreach ($failSequences as $seq) {
    $countZ = $api->countZ($seq);
    $countTkt = $api->countTickets($seq);
    echo (sprintf("Sequence %s: %d Z, %d tickets\n", $seq, $countZ, $countTkt));
    $distinctTkts = [];
    $pages = $countTkt / 100;
    if ($countTkt % 100 != 0) {
        $pages++;
    }
    for ($i = 0; $i < $pages; $i++) {
        $tkts = $api->listTickets($seq, 100, $i);
        for ($j = 0; $j < count($tkts); $j++) {
            if ($tkts[$j]->getContent() === 'EOS') {
                continue;
            }
            $tkt = json_decode($tkts[$j]->getContent(), true);
            $unique = true;
            foreach ($distinctTkts as $distinctTkt) {
                if (sameTicket($distinctTkt, $tkt)) {
                    $unique = false;
                    break;
                }
            }
            if ($unique) {
                $distinctTkts[] = $tkt;
            }
        }
    }
    echo(sprintf("%d distinct tickets:\n", count($distinctTkts)));
    foreach ($distinctTkts as $tkt) {
        echo(sprintf("%s\n", json_encode($tkt, \JSON_PRETTY_PRINT)));
    }
}
