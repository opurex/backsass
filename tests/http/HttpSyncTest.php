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

use \Pasteque\Server\API\VersionAPI;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");
require_once(dirname(dirname(__FILE__)) . "/TestData.php");

class HttpSyncTest extends TestCase
{
    private $curl;
    private static $token;
    private static $dao;
    private static $testData;
    public static function setUpBeforeClass(): void {
        global $dbInfo;
        static::$dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        static::$testData = new TestData();
        static::$testData->install(static::$dao);
        static::$token = obtainToken();
    }

    public static function tearDownAfterClass(): void {
        static::$testData->delete(static::$dao);
        static::$dao->close();
    }    

    protected function setUp(): void {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token]);
    }

    protected function tearDown(): void {
        curl_close($this->curl);
    }

    public function testSync() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/sync'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $this->assertEquals(1, count($data['cashRegisters']));
        $this->assertEquals(1, count($data['paymentmodes']));
        $this->assertEquals(1, count($data['currencies']));
        $this->assertEquals(1, count($data['users']));
        $this->assertEquals(1, count($data['products']));
        $this->assertEquals(0, count($data['floors']));
        $this->assertEquals(1, count($data['options']));
        $this->assertEquals('option', $data['options'][0]['name']);
        $this->markTestIncomplete('Much more to check.');
    }

    public function testSyncWrongCashRegister() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/sync/NotTheCash'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(\Pasteque\Server\Model\CashRegister::class,
                $jsResp['class']);
        $this->assertEquals('NotTheCash', $jsResp['key']['label']);
    }

    public function testSyncCashRegister() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/sync/Cash'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $this->assertEquals(1, count($data['paymentmodes']));
        $this->assertEquals(1, count($data['currencies']));
        $this->assertEquals(1, count($data['users']));
        $this->assertEquals(1, count($data['products']));
        $this->assertEquals(0, count($data['floors']));
        $this->assertEquals(1, count($data['options']));
        $this->assertEquals('option', $data['options'][0]['name']);
        $this->markTestIncomplete('Much more to check.');
    }
}
