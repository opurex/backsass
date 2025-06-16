<?php
// Export/import fiscal tickets from this server to a fiscal mirror one.
// CC-0, WTFPL or anything you want

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\System\Login;

if ($argc < 3) {
    die("mirror_ftickets.php <user> <mirror url> [period]\n"
            . "  Copy the fiscal tickets to a fiscal mirror server.\n"
            . "  The password for the mirror is requested from command line.\n"
            . "  You can store it in a file readable only by the cron user.\n"
            . "  [period] limits the copy from [period] ago to now.\n"
            . "  see https://secure.php.net/manual/en/dateinterval.construct.php\n"
            . "  for the interval format.\n");
}

$user = $argv[1];
$baseUrl = $argv[2];
if (substr($baseUrl, -1) != '/') {
    $baseUrl .= '/';
}

$interval = null;
if ($argc == 4) {
    $interval = $argv[3];
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
echo(sprintf("Exporting %d fiscal tickets for %s.\n", count($fTkts), $user));
$structFTkts = [];
for ($i = 0; $i < count($fTkts); $i++) {
    $structFTkts[] = $fTkts[$i]->toStruct();
}
$jsonFTkts = json_encode($structFTkts);
unset($structFTkts);
unset($fTkts);

// Ask for password for the mirror
$passwd = readline();
// Send them to the mirror
// Login
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $baseUrl . 'api/login');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, ['user' => $user, 'password' => $passwd]);
$resp = curl_exec($curl);
$respCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);
if ($respCode != 200) {
    echo (sprintf("Login to distant server failed, code: %d.\n", $respCode));
    die();
} else if ($resp == 'null') {
    echo ("Login to distant server failed: invalid username or password.\n");
    die();
}
$token = json_decode($resp);
// Send fiscal tickets
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $baseUrl . 'api/fiscal/import');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER,
        [Login::TOKEN_HEADER . ': ' . $token, 'Content-Type: application/json']);
curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonFTkts);
$resp = curl_exec($curl);
$respCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);
if ($respCode != 200) {
    echo (sprintf("Import to distant server failed, code: %d.\n", $respCode));
    die();
}
echo ($resp . "\n");
