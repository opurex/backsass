<?php
// Import script. Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\API\ResourceAPI;
use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\DAO\DAOCondition;

if ($argc < 2) {
    die("updateticketpreview.php <v8 user id>\n"
            . "  Fix Printer.TicketPreview to show all informations "
            . "(copied from Printer.Ticket) \n"
            . "  and add the required \"Duplicata\" tag on the ticket.\n");
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

$api = \Pasteque\Server\API\ResourceAPI::fromApp($ptApp);

// Read Printer.Ticket to start with.
$res = $api->search(new DAOCondition('label', '=', 'Printer.Ticket'));
if (count($res) != 1) {
    die('WTF: ' . $count(res) . ' Printer.Ticket found.\n');
}

$res = $res[0];
$content = stream_get_contents($res->getContent());
// Add Duplicata.
$content = str_replace('${ticket.printId()}', '${ticket.printId()} Duplicata', $content);
// Fix listing taxes.
$content = str_replace('#foreach ($taxinfo in $taxes)',
        '#foreach ($taxline in $ticket.getTaxes())', $content);
$content = str_replace('#set($taxline = $ticket.getTaxLine($taxinfo))', '', $content);

// Save as Printer.TicketPreview.
$res2 = $api->search(new DAOCondition('label', '=', 'Printer.TicketPreview'));
if (count($res2) == 0) {
    $preview = new Resource();
    $preview->setLabel('Printer.TicketPreview');
    $preview->setType(0);
    $preview->setContent($content);
    $api->write($preview);
} else {
    $preview = $res2[0];
    $preview->setContent($content);
    $api->write($preview);
}
