<?php
//    Pasteque server testing
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.
namespace Pasteque\Server;

use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\Model\TicketLine;
use \Pasteque\Server\Model\TicketTax;
use \Pasteque\Server\Model\TicketPayment;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpCustomerTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $cust;

    public static function setUpBeforeClass(): void {
        static::$token = obtainToken();
    }

    public static function tearDownAfterClass(): void {
    }    

    protected function setUp(): void {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token]);
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->cust = new Customer();
        $this->cust->setDispName('Customer');
        $this->dao->write($this->cust);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([Customer::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testPostInvalidDate() {
        $cust = new Customer();
        $cust->setDispName('Customer');
        $struct = $cust->toStruct();
        $struct['expireDate'] = 'notadate';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/customer'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('InvalidDate', $jsResp['constraint']);
        $this->assertEquals(Customer::class, $jsResp['class']);
        $this->assertEquals('expireDate', $jsResp['field']);
        $this->assertEquals('notadate', $jsResp['value']);
    }

    public function testSetBalance() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/customer/%d/balance/1.1', $this->cust->getId())));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $custSnapshot = $this->dao->readSnapshot(Customer::class, $this->cust->getId());
        $this->assertEquals(1.1, $custSnapshot->getBalance());
    }

    public function testSetBalanceNaN() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/customer/%d/balance/1,2', $this->cust->getId())));
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }

    public function testSetBalanceNoCustomer() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/customer/0/balance/1.1'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }

    /** Test the deprecated POST method. When a single customer is sent
     * (even in an array), the result is the single id not in an array. */
    public function testPostOldSingle() {
        $cust = new Customer();
        $cust->setDispName('New customer');
        $struct = $cust->toStruct();
        $struct['tariffArea'] = '0'; // Because Android does
        unset($struct['expireDate']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/customer'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                "customers=[" . json_encode($struct) . ']');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCust = $this->dao->search(Customer::class,
                new DAOCondition('dispName', '=', $cust->getDispName()));
        $this->assertEquals(1, count($dbCust));
        $dbCust = $dbCust[0];
        $jsResp = json_decode($resp, true);
        $this->assertFalse(is_array($jsResp));
        $this->assertEquals($dbCust->getId(), $jsResp);
    }

    /** Test the deprecated POST method. When multiple customers are sent,
     * the result is the array of ids. */
    public function testPostOldMultiple() {
        $cust = new Customer();
        $cust->setDispName('New customer');
        $struct = $cust->toStruct();
        $struct['tariffArea'] = '0'; // Because Android does
        unset($struct['expireDate']);
        $cust2 = new Customer();
        $cust2->setDispName('New customer 2');
        $struct2 = $cust2->toStruct();
        $struct2['tariffArea'] = '0';
        unset($struct2['expireDate']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/customer'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                "customers=[" . json_encode($struct) . ','
                . json_encode($struct2) . ']');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCusts = $this->dao->search(Customer::class,
                new DAOCondition('dispName', '!=', $this->cust->getDispName()),
                null, null, 'dispName');
        $this->assertEquals(2, count($dbCusts));
        $dbCust = $dbCusts[0];
        $dbCust2 = $dbCusts[1];
        $jsResp = json_decode($resp, true);
        $this->assertTrue(is_array($jsResp));
        $this->assertEquals($dbCust->getId(), $jsResp[0]);
        $this->assertEquals($dbCust2->getId(), $jsResp[1]);
    }

}
