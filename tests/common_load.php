<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

// This is the common file to include to load stuff on

namespace Pasteque\Server;

require_once __DIR__ . '/../vendor/autoload.php';

/* Read the config file.
 * Tests uses db info directly from the config file and doesn't rely
 * upon system modules (except for their tests).
 * See config/test-config-sample.ini. */
$cfg = parse_ini_file(dirname(__DIR__) . '/config/test-config.ini');
$dbInfo = ['type' => $cfg['database/type'], 'host' => $cfg['database/host'],
    'port' => $cfg['database/port'], 'name' => $cfg['database/name'],
    'user' => $cfg['database/user'], 'password' => $cfg['database/password']];

if (!empty($cfg['date/timezone'])) {
    date_default_timezone_set($cfg['date/timezone']);
}

function apiUrl($target) {
    global $cfg;
    if (substr($cfg['http/host'], -1) == '/') {
        return $cfg['http/host'] . $target;
    } else {
        return $cfg['http/host'] . '/' . $target;
    }
}

function obtainToken() {
    global $cfg;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, apiUrl('api/login'));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,
            ['user' => $cfg['http/user'],
                    'password' => $cfg['http/password']]);
    $resp = curl_exec($curl);
    curl_close($curl);
    return str_replace('"', '', $resp);
}

function dropDatabase() {
    global $config;
    global $dbModule;
    $db = $dbModule->getDatabase(null);
    $pdo = PDOBuilder::getPdo();
    if ($db['type'] == "mysql") {
        $sqls = array("DROP TABLE APPLICATIONS;", "DROP TABLE ROLES;",
               "DROP TABLE PEOPLE;", "DROP TABLE RESOURCES;",
                "DROP TABLE PROVIDERS;",
                "DROP TABLE DISCOUNTPROFILES;",
                "DROP TABLE DISCOUNTS;",
                "DROP TABLE TARIFFAREAS;",
                "DROP TABLE TAXCUSTCATEGORIES;", "DROP TABLE CUSTOMERS;",
                "DROP TABLE CATEGORIES;", "DROP TABLE TAXCATEGORIES;",
                "DROP TABLE TAXES;", "DROP TABLE ATTRIBUTE;",
                "DROP TABLE ATTRIBUTEVALUE;", "DROP TABLE ATTRIBUTESET;",
                "DROP TABLE ATTRIBUTEUSE;", "DROP TABLE ATTRIBUTESETINSTANCE;",
                "DROP TABLE ATTRIBUTEINSTANCE;", "DROP TABLE PRODUCTS;",
                "DROP TABLE PRODUCTS_CAT;", "DROP TABLE PRODUCTS_COM;",
                "DROP TABLE TARIFFAREAS_PROD;",
                "DROP TABLE SUBGROUPS;", "DROP TABLE SUBGROUPS_PROD;",
                "DROP TABLE LOCATIONS;", "DROP TABLE STOCKDIARY;",
                "DROP TABLE STOCKLEVEL;", "DROP TABLE STOCKCURRENT;",
                "DROP TABLE STOCK_INVENTORY;", "DROP TABLE STOCK_INVENTORYITEM;",
                "DROP TABLE CASHREGISTERS;",
                "DROP TABLE CURRENCIES;", "DROP TABLE CLOSEDCASH;",
                "DROP TABLE PAYMENTMODES;", "DROP TABLE PAYMENTMODES_RETURNS;",
                "DROP TABLE PAYMENTMODES_VALUES;",
                "DROP TABLE ORDERS;", "DROP TABLE ORDERLINES;",
                "DROP TABLE RECEIPTS;", "DROP TABLE TICKETS;",
                "DROP TABLE TICKETLINES;",
                "DROP TABLE PAYMENTS;", "DROP TABLE TAXLINES;",
                "DROP TABLE FLOORS;", "DROP TABLE PLACES;",
                "DROP TABLE RESERVATIONS;", "DROP TABLE RESERVATION_CUSTOMERS;",
                "DROP TABLE THIRDPARTIES;", "DROP TABLE SHAREDTICKETS;",
                "DROP TABLE SHAREDTICKETLINES;");
    } else if ($db['type'] == "postgresql") {
        $sqls = array("DROP TABLE APPLICATIONS;", "DROP TABLE ROLES;",
                "DROP TABLE PEOPLE;", "DROP TABLE RESOURCES;",
                "DROP TABLE PROVIDERS;",
                "DROP SEQUENCE DISCOUNTPROFILES_ID_SEQ CASCADE;",
                "DROP TABLE DISCOUNTPROFILES;",
                "DROP TABLE DISCOUNTS;",
                "DROP TABLE TARIFFAREAS",
                "DROP SEQUENCE TARIFFAREAS_ID_SEQ CASCADE;",
                "DROP TABLE TAXCUSTCATEGORIES;", "DROP TABLE CUSTOMERS;",
                "DROP TABLE CATEGORIES;", "DROP TABLE TAXCATEGORIES;",
                "DROP TABLE TAXES;", "DROP TABLE ATTRIBUTE;",
                "DROP TABLE ATTRIBUTEVALUE;", "DROP TABLE ATTRIBUTESET;",
                "DROP TABLE ATTRIBUTEUSE;", "DROP TABLE ATTRIBUTESETINSTANCE;",
                "DROP TABLE ATTRIBUTEINSTANCE;", "DROP TABLE PRODUCTS;",
                "DROP TABLE PRODUCTS_CAT;", "DROP TABLE PRODUCTS_COM;",
                "DROP TABLE TARIFFAREAS_PROD;",
                "DROP SEQUENCE SUBGROUPS_ID_SEQ", "DROP TABLE SUBGROUPS;",
                "DROP TABLE SUBGROUPS_PROD;",
                "DROP TABLE LOCATIONS;", "DROP TABLE STOCKDIARY;",
                "DROP TABLE STOCKLEVEL;", "DROP TABLE STOCKCURRENT;",
                "DROP SEQUENCE STOCK_INVENTORY_ID_SEQ CASCADE;",
                "DROP SEQUENCE STOCK_INVENTORYITEM_ID_SEQ;",
                "DROP TABLE STOCK_INVENTORY;", "DROP TABLE STOCK_INVENTORYITEM;",
                "DROP SEQUENCE CASHREGISTERS_ID_SEQ CASCADE",
                "DROP TABLE CASHREGISTERS;",
                "DROP SEQUENCE CURRENCIES_ID_SEQ CASCADE;",
                "DROP TABLE CURRENCIES;", "DROP TABLE CLOSEDCASH;",
                "DROP SEQUENCE PAYMENTMODES_ID_SEQ CASCADE;",
                "DROP TABLE PAYMENTMODES;", "DROP TABLE PAYMENTMODES_RETURNS;",
                "DROP TABLE PAYMENTMODES_VALUES;",
                "DROP TABLE ORDERS;",  "DROP TABLE ORDERLINES",
                "DROP TABLE RECEIPTS;", "DROP TABLE TICKETS;",
                "DROP TABLE TICKETLINES;",
                "DROP TABLE PAYMENTS;", "DROP TABLE TAXLINES;",
                "DROP TABLE FLOORS;", "DROP TABLE PLACES;",
                "DROP TABLE RESERVATIONS;", "DROP TABLE RESERVATION_CUSTOMERS;",
                "DROP TABLE THIRDPARTIES;", "DROP TABLE SHAREDTICKETS;",
                "DROP TABLE SHAREDTICKETLINES;");
    }
    for ($i = count($sqls) - 1; $i >= 0; $i--) {
        if ($pdo->exec($sqls[$i]) === false) {
            $info = $pdo->errorInfo();
            Log::error(sprintf('Could not execute %s:, %s %s',
                            $sqls[$i], $info[0], $info[2]));
        }
    }
}
