<?php
// Import script. Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;

if ($argc < 3) {
    die("importdb.php <v7 user id> <v8 user id>\n"
            . "  Import database from a v7 user into a database to a v8 user.\n"
            . "  DB connection details are read by the Database sysmodule of this server.\n"
            . "  The database structure for v8 user must be already set.\n");
}
$v7User = $argv[1];
$v8User = $argv[2];

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

$imgApi = \Pasteque\Server\API\ImageAPI::fromApp($ptApp);

// Set v7 pdo
$pdo = null;
$dsn = null;
$db7 = $ptApp->getDBModule()->getDatabase($v7User);
if ($db7 === false) {
    die(sprintf("V7 user %s not found\n", $v7User));
}
switch ($db7['type']) {
case 'mysql':
    $dsn = 'mysql:dbname=' . $db7['name'] . ';host='
            . $db7['host'] . ';port=' . $db7['port'];
    $options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
    $attributes = array(\PDO::ATTR_CASE => \PDO::CASE_UPPER);
    break;
case 'postgresql':
    $dsn = 'pgsql:dbname=' . $db7['name'] . ';host='
            . $db7['host'] . ';port=' . $db7['port'];
    $options = array();
    $attributes = array(\PDO::ATTR_CASE => \PDO::CASE_UPPER);
    break;
default:
    die('V7 config error');
}
try {
    $pdo = new \PDO($dsn, $db7['user'], $db7['password'], $options);
    foreach ($attributes as $key => $value) {
        $pdo->setAttribute($key, $value);
    }
} catch (\PDOException $e) {
    die('PDO error ' . $e);
  }

function readBool($val) {
    return ((ord($val) == 1) || ($val == "1")
            || $val == "t");
}
function readBin($val, $dbType) {
    if ($val === null) {
        return null;
    }
    switch ($dbType) {
    case 'mysql':
        return $val;
    case 'postgresql':
        $data = fread($val, 2048);
        while (!feof($val)) {
            $data .= fread($val, 2048);
        }
        return $data;
    }
}
function readDate($val) {
    if ($val === null) { return null; }
    $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $val);
    if(!$dateTime) {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $val);
        if (!$dateTime) {
            echo sprintf("Warning: could not read date %s\n.", $val);
            return null;
        } else {
            $dateTime->setTime(0, 0);
        }
    }
    return $dateTime;
}

$imgApi = \Pasteque\Server\API\ImageAPI::fromApp($ptApp);

// Roles
$api = \Pasteque\Server\API\RoleAPI::fromApp($ptApp);
$roleMapping = [];
$stmt = $pdo->prepare('select * from ROLES');
$permDelete = ['<?xml version="1.0" encoding="UTF-8"?>',
        '<!--',
        '    Openbravo POS is a point of sales application designed for touch screens.',
        '    Copyright (C) 2008-2009 Openbravo, S.L.',
        '    http://sourceforge.net/projects/openbravopos',
        '    This file is part of Openbravo POS.',
        '    Openbravo POS is free software: you can redistribute it and/or modify',
        '    it under the terms of the GNU General Public License as published by',
        '    the Free Software Foundation, either version 3 of the License, or',
        '    (at your option) any later version.',
        '    Openbravo POS is distributed in the hope that it will be useful,',
        '    but WITHOUT ANY WARRANTY; without even the implied warranty of',
        '    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the',
        '    GNU General Public License for more details.',
        '    You should have received a copy of the GNU General Public License',
        'along with Openbravo POS.  If not, see <http://www.gnu.org/licenses/>.',
        ' -->',
        '<permissions>',
        '    <class name="',
        '"/>',
        '    <!-- <class name="sales.ChangeTaxOptions"/> -->',
        '</permissions>'];
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $name = $row['NAME'];
        $perms = $row['PERMISSIONS'];
        foreach ($permDelete as $del) {
            $perms = str_replace($del, "", $perms);
        }
        $perms = str_replace("\n", ";", $perms);
        $perms = str_replace("\r", ";", $perms);
        $perms = explode(';', $perms);
        $newPerms = [];
        foreach ($perms as $perm) {
            $perm = trim($perm);
            if (!empty($perm)) {
                $newPerms[] = $perm;
            }
        }
        $role = new \Pasteque\Server\Model\Role();
        $role->setName($name);
        foreach ($newPerms as $perm) {
            $role->addPermission($perm);
        }
        $api->write($role);
        $roleMapping[$row['ID']] = $role;
    }
} else {
    $err = $stmt->errorInfo();
    die('ROLE error' . $err[2]);
}

// Users
$api = \Pasteque\Server\API\UserAPI::fromApp($ptApp);
$userMapping = [];
$stmt = $pdo->prepare('select * from PEOPLE');
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $user = new \Pasteque\Server\Model\User();
        $user->setName($row['NAME']);
        $user->setPassword($row['APPPASSWORD']);
        $user->setCard($row['CARD']);
        $user->setRole($roleMapping[$row['ROLE']]);
        $user->setActive(readBool($row['VISIBLE']));
        $api->write($user);
        $userMapping[$row['ID']] = $user->getId();
    }
} else {
    $err = $stmt->errorInfo();
    die('PEOPLE error' . $err[2]);
}

// Resources
$api = \Pasteque\Server\API\ResourceAPI::fromApp($ptApp);
$resourceMapping = [];
$stmt = $pdo->prepare('select * from RESOURCES');
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $res = new \Pasteque\Server\Model\Resource();
        $res->setLabel($row['NAME']);
        $res->setType($row['RESTYPE']);
        $res->setContent($row['CONTENT']);
        $api->write($res);
    }
} else {
    $err = $stmt->errorInfo();
    die('RESOURCES error' . $err[2]);
}

// CashRegisters
$api = \Pasteque\Server\API\CashregisterAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from CASHREGISTERS');
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $cashRegister = new \Pasteque\Server\Model\CashRegister();
        $cashRegister->setLabel($row['NAME']);
        $api->write($cashRegister);
    }
} else {
    $err = $stmt->errorInfo();
    die('CASHREGISTERS error' . $err[2]);
}

// Currencies
$api = \Pasteque\Server\API\CurrencyAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from CURRENCIES');
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $currency = new \Pasteque\Server\Model\Currency();
        $currency->setLabel($row['NAME']);
        $currency->setSymbol($row['SYMBOL']);
        $currency->setDecimalSeparator($row['DECIMALSEP']);
        $currency->setThousandsSeparator($row['THOUSANDSSEP']);
        $currency->setRate($row['RATE']);
        $currency->setFormat($row['FORMAT']);
        $currency->setMain(readBool($row['MAIN']));
        $currency->setVisible(readBool($row['ACTIVE']));
        $api->write($currency);
    }
} else {
    $err = $stmt->errorInfo();
    die('CURRENCIES error' . $err[2]);
}

// DiscountProfiles
$api = \Pasteque\Server\API\DiscountprofileAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from DISCOUNTPROFILES');
$discountProfileMapping = [];
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $disc = new \Pasteque\Server\Model\DiscountProfile();
        $disc->setLabel($row['NAME']);
        $disc->setRate($row['RATE']);
        $disc->setDispOrder($row['DISPORDER']);
        $api->write($disc);
        $discountProfileMapping[$row['ID']] = $disc;
    }
} else {
    $err = $stmt->errorInfo();
    die('DISCOUNTPROFILES error' . $err[2]);
}

// Discounts
$api = \Pasteque\Server\API\DiscountAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from DISCOUNTS');
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $disc = new \Pasteque\Server\Model\Discount();
        $disc->setLabel($row['LABEL']);
        $disc->setStartDate(readDate($row['STARTDATE']));
        $disc->setEndDate(readDate($row['ENDDATE']));
        $disc->setRate($row['RATE']);
        $disc->setBarcode($row['BARCODE']);
        $disc->setBarcodeType($row['BARCODETYPE']);
        $disc->setDispOrder($row['DISPORDER']);
        $api->write($disc);
    }
} else {
    $err = $stmt->errorInfo();
    // Table DISCUNTS may not exists. Not a big deal.
    // It was created on 6.0 but not included in upgrade scripts.
    // Nobody uses it probably.
    echo('DISCOUNTS error ' . $err[2] . "\n");
}

// PaymentModes, PaymentModeReturns and PaymentModeValues
$api = \Pasteque\Server\API\PaymentmodeAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from PAYMENTMODES');
if ($stmt->execute()) {
    $stmt2 = $pdo->prepare('select * from PAYMENTMODES_RETURNS where PAYMENTMODE_ID = :id');
    while ($row = $stmt->fetch()) {
        $pm = new \Pasteque\Server\Model\PaymentMode();
        $pm->setReference($row['CODE']);
        $pm->setLabel($row['NAME']);
        $pm->setBackLabel($row['BACKNAME']);
        $pm->setType($row['FLAGS']);
        $pm->setVisible(readBool($row['ACTIVE']));
        $pm->setDispOrder($row['DISPORDER']);
        $stmt2->bindValue(':id', $row['ID']);
        if ($stmt2->execute()) {
            while ($row2 = $stmt2->fetch()) {
                $return = new \Pasteque\Server\Model\PaymentModeReturn();
                $return->setMinAmount($row2['MIN']);
                $pm->addReturn($return);
            }
        } else {
            $err = $stmt2->errorInfo();
            die('PAYMENTMODES_RETURNS error' . $err[2]);
        }
        if ($row['CODE'] == 'cash') {
            // Add value buttons that no client uses.
        }
        $api->write($pm);
    }
} else {
    $err = $stmt->errorInfo();
    die('PAYMENTMODES error' . $err[2]);
}

// Places and Floors
$api = \Pasteque\Server\API\PlaceAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from FLOORS');
$floorMapping = [];
$dispOrder = 0;
if ($stmt->execute()) {
    $stmt2 = $pdo->prepare('select * from PLACES where FLOOR = :id');
    while ($row = $stmt->fetch()) {
        $dispOrder++;
        $floor = new \Pasteque\Server\Model\Floor();
        $floor->setLabel($row['NAME']);
        $floor->setDispOrder($dispOrder);
        $stmt2->bindValue(':id', $row['ID']);
        if ($stmt2->execute()) {
            while ($row2 = $stmt2->fetch()) {
                $place = new \Pasteque\Server\Model\Place();
                $place->setLabel($row2['NAME']);
                $place->setX($row2['X']);
                $place->setY($row2['Y']);
                $floor->addPlace($place);
            }
        } else {
            $err = $stmt2->errorInfo();
            die('PLACES error' . $err[2]);
        }
        $api->write($floor);
    }
} else {
    $err = $stmt->errorInfo();
    die('FLOORS error' . $err[2]);
}

// Taxes
$api = \Pasteque\Server\API\TaxAPI::fromApp($ptApp);
$taxMapping = [];
$stmt = $pdo->prepare('select * from TAXCATEGORIES');
if ($stmt->execute()) {
    $stmt2 = $pdo->prepare('select * from TAXES where CATEGORY = :id order by VALIDFROM desc');
    while ($row = $stmt->fetch()) {
        $stmt2->bindValue(':id', $row['ID']);
        if ($stmt2->execute()) {
            if ($row2 = $stmt2->fetch()) {
                $tax = new \Pasteque\Server\Model\Tax();
                $tax->setLabel($row2['NAME']);
                $tax->setRate($row2['RATE']);
                $api->write($tax);
                // row['ID'] and not row2 because mapped by TAXCATEGORIES.ID
                $taxMapping[$row['ID']] = $tax;
            }
        } else {
            $err = $stmt2->errorInfo();
            die('TAXES error' . $err[2]);
        }
    }
} else {
    $err = $stmt->errorInfo();
    die('TAXCATEGORIES error' . $err[2]);
}

// Categories.
$api = \Pasteque\Server\API\CategoryAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from CATEGORIES');
$categoryMapping = [];
$categoryParents = [];
if ($stmt->execute()) {
    // Add all without parents
    while ($row = $stmt->fetch()) {
        $cat = new \Pasteque\Server\Model\Category();
        $cat->setLabel($row['NAME']);
        if (!empty($row['REFERENCE'])) {
            $cat->setReference($row['REFERENCE']);
        }
        if (!empty($row['DISPORDER'])) {
            $cat->setDispOrder($row['DISPORDER']);
        }
        if (!empty($row['PARENTID'])) {
            $categoryParents[$row['ID']] = $row['PARENTID'];
        }
        $api->write($cat);
        $categoryMapping[$row['ID']] = $cat;
        if (!empty($row['IMAGE'])) {
            $imgBin = readBin($row['IMAGE'], $db7['type']);
            $img = new \Pasteque\Server\Model\Image();
            $img->setModel(\Pasteque\Server\Model\Image::MODEL_CATEGORY);
            $img->setModelId($cat->getId());
            $img->setImage($imgBin);
            $imgApi->write($img);
        }
    }
    // Set parents
    foreach ($categoryParents as $id7 => $pid7) {
        $cat = $categoryMapping[$id7];
        $cat->setParent($categoryMapping[$pid7]);
        $api->write($cat);
    }
} else {
    $err = $stmt->errorInfo();
    die('DISCOUNTS error' . $err[2]);
}

// Products and compositions
$api = \Pasteque\Server\API\ProductAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from PRODUCTS');
$prdMapping = [];
$compositions = [];
if ($stmt->execute()) {
    $stmt2 = $pdo->prepare('select * from PRODUCTS_CAT where PRODUCT = :id');
    while ($row = $stmt->fetch()) {
        if (readBool($row['DELETED'])) {
            continue;
        }
        $prd = new \Pasteque\Server\Model\Product();
        $prd->setReference($row['REFERENCE']);
        $prd->setLabel($row['NAME']);
        $prd->setBarcode($row['CODE']);
        $prd->setPriceBuy($row['PRICEBUY']);
        $prd->setPriceSell($row['PRICESELL']);
        $prd->setVisible(true);
        $prd->setScaled(readbool($row['ISSCALE']));
        $stmt2->bindValue(':id', $row['ID']);
        if ($stmt2->execute()) {
            if ($row2 = $stmt2->fetch()) {
                if (!empty($row2['CATORDER'])) {
                    $prd->setDispOrder($row2['CATORDER']);
                }
            } else {
                // Not sold, don't include it to clean database.
                continue;
            }
        } else {
            // Ignore
        }
        $prd->setDiscountEnabled(readBool($row['DISCOUNTENABLED']));
        $prd->setDiscountRate($row['DISCOUNTRATE']);
        $prd->setPrepay(readBool($row['ISPREPAY']));
        $prd->setComposition(readBool($row['ISCOMPOSITION']));
        $prd->setCategory($categoryMapping[$row['CATEGORY']]);
        $prd->setTax($taxMapping[$row['TAXCAT']]);
        if ($prd->isComposition()) {
            $compositions[$row['ID']] = $prd;
        }
        $api->write($prd);
        $prdMapping[$row['ID']] = $prd;
        if (!empty($row['IMAGE'])) {
            $imgBin = readBin($row['IMAGE'], $db7['type']);
            $img = new \Pasteque\Server\Model\Image();
            $img->setModel(\Pasteque\Server\Model\Image::MODEL_PRODUCT);
            $img->setModelId($prd->getId());
            $img->setImage($imgBin);
            $imgApi->write($img);
        }
    }
    // Read compositions
    $stmt3 = $pdo->prepare('select * from SUBGROUPS where COMPOSITION = :id '
            . 'order by DISPORDER asc');
    $stmt4 = $pdo->prepare('select * from SUBGROUPS_PROD where SUBGROUP = :id '
            . 'order by DISPORDER asc');
    foreach ($compositions as $id => $prd) {
        $stmt3->bindValue(':id', $id);
        if ($stmt3->execute()) {
            while ($rowGrp = $stmt3->fetch()) {
                $group = new \Pasteque\Server\Model\CompositionGroup();
                $group->setLabel($rowGrp['NAME']);
                $dispOrder = ($rowGrp['DISPORDER'] === null) ?
                        0 : $rowGrp['DISPORDER'];
                $group->setDispOrder($dispOrder);
                $stmt4->bindValue(':id', $rowGrp['ID']);
                if ($stmt4->execute()) {
                    while ($rowPrd = $stmt4->fetch()) {
                        $grpPrd = new \Pasteque\Server\Model\CompositionProduct();
                        $dispOrder = ($rowPrd['DISPORDER'] === null) ?
                                0 : $rowPrd['DISPORDER'];
                        $grpPrd->setDispOrder($dispOrder);
                        $grpPrd->setProduct($prdMapping[$rowPrd['PRODUCT']]);
                        $group->addCompositionProducts($grpPrd);
                    }
                } else {
                    $err = $stmt4->errorInfo();
                    die('SUBGROUPS_PROD error ' . $err[2]);
                }
                $prd->addCompositionGroup($group);
                $api->write($prd);
            }
        } else {
            $err = $stmt3->errorInfo();
            die('SUBGROUPS error ' . $err[2]);
        }
    }
} else {
    $err = $stmt->errorInfo();
    die('PRODUCTS error' . $err[2]);
}


// TariffAreas
$api = \Pasteque\Server\API\TariffareaAPI::fromApp($ptApp);
$areaMapping = [];
$stmt = $pdo->prepare('select * from TARIFFAREAS');
if ($stmt->execute()) {
    $stmt2 = $pdo->prepare('select * from TARIFFAREAS_PROD where TARIFFID = :id');
    while ($row = $stmt->fetch()) {
        $stmt2->bindValue(':id', $row['ID']);
        $ta = new \Pasteque\Server\Model\TariffArea();
        $ta->setLabel($row['NAME']);
        $ta->setDispOrder(0);
        if ($stmt2->execute()) {
            while ($row2 = $stmt2->fetch()) {
                $price = new \Pasteque\Server\Model\TariffAreaPrice();
                $price->setPrice($row2['PRICESELL']);
                $price->setProduct($prdMapping[$row2['PRODUCTID']]);
                $ta->addPrice($price);
            }
        } else {
            $err = $stmt2->errorInfo();
            die('TARIFFAREAS_PROD error' . $err[2]);
        }
        $api->write($ta);
        $areaMapping[$row['ID']] = $ta;
    }
} else {
    $err = $stmt->errorInfo();
    die('TARIFFAREAS error' . $err[2]);
}


// Customers
$api = \Pasteque\Server\API\CustomerAPI::fromApp($ptApp);
$stmt = $pdo->prepare('select * from CUSTOMERS');
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        $customer = new \Pasteque\Server\Model\Customer();
        $customer->setDispName($row['NAME']);
        if (!empty($row['CARD'])) {
            $customer->setCard(($row['CARD']===null)?'':$row['CARD']);
        }
        $customer->setMaxDebt($row['MAXDEBT']);
        $debt = ($row['CURDEBT']===null)?0.0:$row['CURDEBT'];
        $prepaid = $row['PREPAID'];
        $customer->setFirstName(($row['FIRSTNAME']===null)?'':$row['FIRSTNAME']);
        $customer->setLastName(($row['LASTNAME']===null)?'':$row['LASTNAME']);
        $customer->setEmail(($row['EMAIL']===null)?'':$row['EMAIL']);
        $customer->setPhone1(($row['PHONE']===null)?'':$row['PHONE']);
        $customer->setPhone2(($row['PHONE2']===null)?'':$row['PHONE2']);
        $customer->setFax(($row['FAX']===null)?'':$row['FAX']);
        $customer->setAddr1(($row['ADDRESS']===null)?'':$row['ADDRESS']);
        $customer->setAddr2(($row['ADDRESS2']===null)?'':$row['ADDRESS2']);
        $customer->setZipCode(($row['POSTAL']===null)?'':$row['POSTAL']);
        $customer->setCity(($row['CITY']===null)?'':$row['CITY']);
        $customer->setRegion(($row['REGION']===null)?'':$row['REGION']);
        $customer->setCountry(($row['COUNTRY']===null)?'':$row['COUNTRY']);
        $customer->setNote(($row['NOTES']===null)?'':$row['NOTES']);
        $customer->setVisible(readBool($row['VISIBLE']));
        $customer->setExpireDate(readDate($row['EXPIREDATE']));
        if (!empty($row['DISCOUNTPROFILE_ID'])) {
            $customer->setDiscountProfile($discountProfileMapping[$row['DISCOUNTPROFILE_ID']]);
        }
        if (!empty($row['TARIFFAREA_ID'])) {
            $customer->setTariffArea($areaMapping[$row['TARIFFAREA_ID']]);
        }
        if (!empty($row['TAXCATEGORY'])) {
            $customer->setTax($taxMapping[$row['TAXCATEGORY']]);
        }
        $api->write($customer);
        $balance = $prepaid - $debt;
        if ($balance > 0.005 || $balance < -0.005) {
            $api->setBalance($customer->getId(), $balance);
        }
    }
} else {
    $err = $stmt->errorInfo();
    die('CUSTOMERS error' . $err[2]);
}

// dblevel option
$api = \Pasteque\Server\CommonAPI\VersionAPI::fromApp($ptApp);
$api->setLevel(8);
