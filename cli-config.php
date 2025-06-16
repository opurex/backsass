<?php
namespace Pasteque\Server;
use \Pasteque\Server\System\DAO\DoctrineDAO;

$cfg = parse_ini_file(__DIR__ . '/config/config.ini');
$dbInfo = ['type' => $cfg['database/type'], 'host' => $cfg['database/host'],
    'port' => $cfg['database/port'], 'name' => $cfg['database/name'],
    'user' => $cfg['database/user'], 'password' => $cfg['database/password']];
$dao = new DoctrineDAO($dbInfo, ['debug' => false]);
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($dao->getEntityManager());

