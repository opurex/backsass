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

use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpCashRegisterTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $cr;

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
        $this->cr = new CashRegister();
        $this->cr->setReference('cr');
        $this->cr->setLabel('CashRegister');
        $this->dao->write($this->cr);
        $this->dao->commit();
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        $all = $this->dao->search(CashRegister::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetAll() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cashregister/getAll'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertEquals(1, count($data));
        $this->assertEquals($this->cr->getReference(), $data[0]['reference']);
        $this->assertEquals($this->cr->getLabel(), $data[0]['label']);
    }

    public function testPostNew() {
        $newCr = new CashRegister();
        $newCr->setReference('New CR');
        $newCr->setLabel('New Cash Register');
        $postData = $newCr->toStruct();
        unset($postData['id']);
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cashregister'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbUser = $this->dao->search(CashRegister::class,
                new DAOCondition('reference', '=', 'New CR'));
        $this->assertEquals(1, count($dbUser));
    }

    public function testPostUpdate() {
        $this->cr->setLabel('Edited CR');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cashregister'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->cr->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(1, $this->dao->count(CashRegister::class));
        $dbCR = $this->dao->readSnapshot(CashRegister::class, $this->cr->getId());
        $this->assertEquals('Edited CR', $dbCR->getLabel());
    }

    public function testPostRefExisting() {
        $cr = new CashRegister();
        $cr->setLabel('new');
        $cr->setReference($this->cr->getReference());
        $struct = $cr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cashregister'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($this->cr->getReference(), $jsResp['value']);
        $this->assertEquals($this->cr->getReference(),
                $jsResp['key']['reference']);
    }


    public function testPutOk() {
        $cr = new CashRegister();
        $cr->setLabel('New');
        $cr->setReference('New ref');
        $struct = $cr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashregister/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(CashRegister::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbCurr));
    }

    public function testPutId() {
        $cr = new CashRegister();
        $cr->setLabel('New');
        $cr->setReference('New ref');
        $struct = $cr->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashregister/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }

    public function testPutRefMismatch() {
        $cr = new CashRegister();
        $cr->setLabel('New');
        $cr->setReference('New ref');
        $struct = $cr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/cashregister/notNewRef'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(CashRegister::class,
                new DAOCondition('reference', '=', 'notNewRef'));
        $this->assertEquals(1, count($dbCurr));
    }

    public function testPutRefExisting() {
        $cr = new CashRegister();
        $cr->setLabel('New');
        $cr->setReference($this->cr->getReference());
        $struct = $cr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashregister/cr',
                        urlencode($this->cr->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($this->cr->getReference(), $jsResp['value']);
        $this->assertEquals($this->cr->getReference(),
                $jsResp['key']['reference']);
    }


    public function testPatchOk() {
        $struct = $this->cr->toStruct();
        unset($struct['id']);
        $struct['label'] = 'edited';
        $struct['reference'] = 'newref';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashregister/%s',
                        urlencode($this->cr->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('edited', $jsResp['label']);
        $dbCr = $this->dao->search(CashRegister::class);
        $this->assertEquals(1, count($dbCr));
        $dbCr = $this->dao->readSnapshot(CashRegister::class,
                $this->cr->getId());
        $this->assertEquals('edited', $dbCr->getLabel());
        $this->assertEquals('newref', $dbCr->getReference());
    }

    public function testPatchId() {
        $struct = $this->cr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashregister/%s',
                        $this->cr->getReference())));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }

    public function testPatchRefExisting() {
        $cr = new CashRegister();
        $cr->setReference('ref2');
        $cr->setLabel('label2');
        $this->dao->write($cr);
        $this->dao->commit();
        $struct = $this->cr->toStruct();
        unset($struct['id']);
        $struct['reference'] = 'ref2';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashregister/%s',
                        $this->cr->getReference())));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('ref2', $jsResp['value']);
        $this->assertEquals($this->cr->getReference(),
                $jsResp['key']['reference']);
    }

    public function testPatchNotFound() {
        $struct = $this->cr->toStruct();
        unset($struct['id']);
        $struct['reference'] = 'notFound';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/cashregister/notFound'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals('notFound', $jsResp['key']['reference']);
    }
}
