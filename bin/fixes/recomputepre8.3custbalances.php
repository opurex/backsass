<?php
// Import script. Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin\fixes;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\API\CashsessionAPI;
use \Pasteque\Server\API\TicketAPI;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\CashSessionCustBalance;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\System\DAO\DAOCondition;

if ($argc < 2) {
    die("recomputepre8.3custbalances.php <v8 user id>\n"
            . "  Recompute the customers' balance in Z tickets.\n"
            . "  CashsessionAPI->summary gave incorrect values before 8.3\n"
            . "  but it didn't affected the customer's balance.\n");
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

$db = $ptApp->getDbModule()->getDatabase($v8User);
if ($db === false) {
    die(sprintf("V8 user %s not found\n", $v8User));
}
$ptApp->login($ptApp->getIdentModule()->getUser($v8User));

$dao = $ptApp->getDao();

$sessionToFix = [];
$fixCount = 0;
$fixSessCount = 0;

/* Get all tickets where the customer's balance was changed
 * and group them by session. */

$tktApi = new TicketAPI($dao);
$tkts = $tktApi->search(new DAOCondition('custBalance', '!=', 0.0), null, null, 'number');
foreach ($tkts as $tkt) {
    $sessId = sprintf('%d-%d', $tkt->getCashRegister()->getId(), $tkt->getSequence());
    if (!array_key_exists($sessId, $sessionToFix)) {
        $sessionToFix[$sessId] = ['cashRegister' => $tkt->getCashRegister(),
                'sequence' => $tkt->getSequence(), 'customers' => []];
        $fixSessCount++;
    }
    $custId = $tkt->getCustomer()->getId();
    if (!array_key_exists($tkt->getCustomer()->getId(),
            $sessionToFix[$sessId]['customers'])) {
        $sessionToFix[$sessId]['customers'][$tkt->getCustomer()->getId()] = true;
        $fixCount++;
    }
}

printf("Found %d balances within %d sessions.\n",
        $fixCount, $fixSessCount);

if ($fixCount > 0 || $fixSessCount > 0) {
    printf("Balance num, session num - operation\n------------------------------------\n");
}

// Recompute the customer's balance for each session and fix it if required.

$sessionApi = new CashsessionAPI($dao);
$balanceIndex = 1;
$sessionIndex = 1;
foreach ($sessionToFix as $sessionInfo) {
    $session = CashSession::load($sessionInfo, $dao);
    $summary = $sessionApi->summary($session);
    $clean = true;
    foreach ($summary->get('custBalances') as $fixedBalance) {
        $balanceFound = false;
        foreach ($session->getCustBalances() as $balance) {
            if ($balance->getCustomer()->getId() == $fixedBalance->get('customer')) {
                $balanceFound = true;
                if (abs($balance->getBalance() - $fixedBalance->get('balance')) > 0.005) {
                    $clean = false;
                    printf("%3d, %3d - Fixing balance from %.2f to %.2f for %s, session %s-%d\n",
                            $balanceIndex, $sessionIndex,
                            $balance->getBalance(), $fixedBalance->get('balance'),
                            $balance->getCustomer()->getDispName(),
                            $session->getCashRegister()->getReference(),
                            $session->getSequence());
                    $balance->setBalance($fixedBalance->get('balance'));
                } else {
                    printf("%3d, %3d - Balance %.2f for %s is coherent, session %s-%d\n",
                            $balanceIndex, $sessionIndex,
                            $balance->getBalance(), $balance->getCustomer()->getDispName(),
                            $session->getCashRegister()->getReference(),
                            $session->getSequence());
                }
                break;
            }
        }
        if (!$balanceFound) {
            $clean = false;
            $custBalance = new CashSessionCustBalance();
            $customer = $dao->read(Customer::class, $fixedBalance->get('customer'));
            $custBalance->setCustomer($customer);
            $custBalance->setBalance($fixedBalance->get('balance'));
            printf("%3d, %3d - Adding balance %.2f for %s, session %s-%d\n",
                $balanceIndex, $sessionIndex,
                $fixedBalance->get('balance'), $customer->getDispName(),
                $session->getCashRegister()->getReference(),
                $session->getSequence());
            $session->addCustBalances($custBalance);
        }
        $balanceIndex++;
    }
    $sessionIndex++;
    if (!$clean) {
        $dao->write($session);
    }
}
$dao->commit();

