<?php
// Import script. Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin\fixes;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\API\CashregisterAPI;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;

if ($argc < 2) {
    die("addperpetualcs.php <v8 user id>\n"
            . "  Add the missing perpetual cs field.\n");
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

$isMirror = $ptApp->isFiscalMirror();

$db = $ptApp->getDbModule()->getDatabase($v8User);
if ($db === false) {
    die(sprintf("V8 user %s not found\n", $v8User));
}
$ptApp->login($ptApp->getIdentModule()->getUser($v8User));

$em = $ptApp->getDao()->getEntityManager();
$sqls = [];
switch ($db['type']) {
    case 'postgresql':
        if (!$isMirror) {
            $sqls[] = 'ALTER TABLE sessions ADD csperpetual DOUBLE PRECISION NOT NULL DEFAULT 0.0;';
        }
        $sqls[] = 'CREATE SEQUENCE archiverequests_id_seq INCREMENT BY 1 MINVALUE 1 START 1;';
        $sqls[] = 'CREATE TABLE archives (number INT NOT NULL, info TEXT NOT NULL, content BYTEA NOT NULL, contentHash VARCHAR(255) NOT NULL, signature VARCHAR(255) NOT NULL, PRIMARY KEY(number));';
        $sqls[] = 'CREATE TABLE archiverequests (id INT NOT NULL, startDate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, stopDate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, processing BOOLEAN NOT NULL, PRIMARY KEY(id));';
        break;
    case 'mysql':
        if (!$isMirror) {
            $sqls[] = 'ALTER TABLE sessions ADD csperpetual DOUBLE PRECISION NOT NULL DEFAULT 0.0;';
        }
        $sqls[] = 'CREATE TABLE archives (number INT NOT NULL, info LONGTEXT NOT NULL, content LONGBLOB NOT NULL, contentHash VARCHAR(255) NOT NULL, signature VARCHAR(255) NOT NULL, PRIMARY KEY(number)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;';
        $sqls[] = 'CREATE TABLE archiverequests (id INT AUTO_INCREMENT NOT NULL, startDate DATETIME NOT NULL, stopDate DATETIME NOT NULL, processing TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;';
        break;
    case 'sqlite':
        if (!$isMirror) {
            $sqls = 'ALTER TABLE sessions ADD COLUMN csperpetual DOUBLE PRECISION NOT NULL DEFAULT 0.0;';
        }
        $sqls = 'CREATE TABLE archives (number INTEGER NOT NULL, info CLOB NOT NULL, content BLOB NOT NULL, contentHash VARCHAR(255) NOT NULL, signature VARCHAR(255) NOT NULL, PRIMARY KEY(number));';
        $sqls = 'CREATE TABLE archiverequests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, startDate DATETIME NOT NULL, stopDate DATETIME NOT NULL, processing BOOLEAN NOT NULL);';
        break;
    default:
        die(sprintf("Unknown database type %s\n", $db['type']));
}
foreach ($sqls as $sql) {
    $stmt = $em->getConnection()->prepare($sql);
    $stmt->execute();
}

if ($isMirror) {
    return;
}

$crAPI = new CashregisterAPI($ptApp->getDao());
$cashRegisters = $crAPI->getAll();
foreach ($cashRegisters as $cr) {
    $sessions = $ptApp->getDao()->search(CashSession::class, null, null, null,
            'sequence');
    $total = 0.0;
    foreach ($sessions as $session) {
        $session->setCSPerpetual($total + $session->getCS());
        $ptApp->getDao()->write($session);
        $total = $session->getCSPerpetual();
    }
}
$ptApp->getDao()->commit();

$sql = '';
switch ($db['type']) {
    case 'postgresql':
    case 'mysql':
        $sql = 'ALTER TABLE sessions ALTER COLUMN csperpetual DROP DEFAULT';
        break;
    case 'sqlite':
        echo("Warning: The default value for csPerpetual is still there.\n");
        break;
    default:
        die(sprintf("Unknown database type %s\n", $db['type']));
}
if ($sql != '') {
    $stmt = $em->getConnection()->prepare($sql);
    $stmt->execute();
}
