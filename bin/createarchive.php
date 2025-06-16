<?php
// Import script. Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\CommonAPI\ArchiveAPI;

if ($argc < 2) {
    die("createarchive.php <user_id> [max_exec_time]\n"
            . "  Run the generation of archives one after the other.\n"
            . "  user_id         the pasteque user\n"
            . "  max_exec_time   Stop generating next pending archives when the\n"
            . "                  last generation exceeded time limit (in seconds).\n");
}
$ptUser = $argv[1];
$timeLimit = null;
if ($argc > 2) {
    $timeLimet = intval($argv[2]);
}

$projectRoot = dirname(__DIR__);
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

if ($ptApp->getDbModule()->getDatabase($ptUser) === false) {
    die(sprintf("Pasteque user %s not found\n", $ptUser));
}
$ptApp->login($ptApp->getIdentModule()->getUser($ptUser));
$dao = $ptApp->getDao();

$startTime = new \DateTime();
$startTime = $startTime->getTimestamp();

$api = ArchiveAPI::fromApp($ptApp);
$request = $api->getFirstAvailableRequest();
while ($request != null) {
    $now = new \DateTime();
    $now = $now->getTimestamp();
    if (($timeLimit != null) && ($now - $startTime > $timeLimit)) {
        break;
    }
    $api->createArchive($request->getId());
    $request = $api->getFirstAvailableRequest();
}
