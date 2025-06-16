<?php
// Export/import fiscal tickets from this server to a fiscal mirror one.
// CC-0, WTFPL or anything you want

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\System\Login;

if ($argc < 2) {
    die("export_ftickets.php <user> [period]\n"
            . "  Export the fiscal tickets and send them to the ouput stream.\n"
            . "  [period] limits the copy from [period] ago to now.\n"
            . "  see https://secure.php.net/manual/en/dateinterval.construct.php\n"
            . "  for the interval format.\n");
}

$user = $argv[1];
$interval = null;
if ($argc == 3) {
    $interval = $argv[2];
}

$projectRoot = dirname(dirname(__DIR__));
require_once $projectRoot . '/vendor/autoload.php';

// Set v8
$cfgFile = $projectRoot . '/config/config.ini';
if (!is_readable($cfgFile)) {
    // Check for a moved configuration
    $envCfgFile = getenv('PT_CONFIG_' . preg_replace('/[^[:alnum:]]/', '_', $projectRoot));
    if (($envCfgFile === false) || !is_readable($envCfgFile)) {
        die("No config file found.\n");
    }
    $cfgFile = $envCfgFile;
}

$ptApp = AppContext::loadFromConfig(parse_ini_file($cfgFile));
unset($cfgFile);
$ptUser = $ptApp->getIdentModule()->getUser($user);
if ($ptUser == null) {
    die(sprintf("User %s not found.\n", $user));
}
$ptApp->login($ptUser);
unset($ptUser);

$api = \Pasteque\Server\API\FiscalAPI::fromApp($ptApp);

// Export fiscal tickets to json format
$fTkts = null;
if ($interval === null) {
    $fTkts = $api->getAll();
} else {
    try {
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval($interval));
    } catch (\Exception $e) {
        die(sprintf("Invalid interval '%s'.\n", $interval));
    }
    $fTkts = $api->search(new \Pasteque\Server\System\DAO\DAOCondition('date', '>=', $startDate), null, null, ['type', 'sequence', 'number']);
}

$structFTkts = [];
for ($i = 0; $i < count($fTkts); $i++) {
    $structFTkts[] = $fTkts[$i]->toStruct();
}
$jsonFTkts = json_encode($structFTkts);
unset($structFTkts);
unset($fTkts);

print($jsonFTkts);
print("\n");
