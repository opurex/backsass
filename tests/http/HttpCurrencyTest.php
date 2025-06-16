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

use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpCurrencyTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $curr;

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
        $this->curr = new Currency();
        $this->curr->merge(['reference' => 'Reference', 'label' => 'label',
                'main' => true], $this->dao);
        $this->dao->write($this->curr);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([Currency::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testPostNew() {
        $curr = new Currency();
        $curr->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(Currency::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbCurr));
    }

    public function testPostEdit() {
        $this->curr->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $this->curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(Currency::class);
        $this->assertEquals(1, count($dbCurr));
        $this->assertEquals('New ref', $dbCurr[0]->getReference());
        $this->assertEquals('New label', $dbCurr[0]->getLabel());
    }

    public function testPostRefExisting() {
        $curr = new Currency();
        $curr->merge(['reference' => 'Reference', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency'));
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
        $this->assertEquals(Currency::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('Reference', $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }


    public function testPutOk() {
        $curr = new Currency();
        $curr->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/currency/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(Currency::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbCurr));
    }

    public function testPutId() {
        $curr = new Currency();
        $curr->merge(['reference' => 'newref', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/newref'));
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
        $curr = new Currency();
        $curr->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/notNewRef'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(Currency::class,
                new DAOCondition('reference', '=', 'notNewRef'));
        $this->assertEquals(1, count($dbCurr));
    }

    public function testPutRefExisting() {
        $curr = new Currency();
        $curr->merge(['reference' => 'Reference', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/Reference'));
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
        $this->assertEquals(Currency::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('Reference', $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }


    public function testPatchOk() {
        $curr = new Currency();
        $curr->merge(['reference' => 'Reference', 'label' => 'New label',
                'main' => true], $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/Reference'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('New label', $jsResp['label']);
        $dbCurr = $this->dao->search(Currency::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbCurr));
        $dbCurr = $this->dao->readSnapshot(Currency::class,
                $dbCurr[0]->getId());
        $this->assertEquals('New label', $dbCurr->getLabel());
    }

    /** @depends testPatchOk */
    public function testPatchUnsetMain() {
        $curr = new Currency();
        $curr->merge(['reference' => 'Reference', 'label' => 'New label'],
                $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/Reference'));
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
        $this->assertEquals('DefaultRequired', $jsResp['constraint']);
        $this->assertEquals(Currency::class, $jsResp['class']);
        $this->assertEquals('main', $jsResp['field']);
        $this->assertEquals(false, $jsResp['value']);
        $this->assertEquals(true, $jsResp['key']['main']);
    }

    public function testPatchId() {
        $curr = new Currency();
        $curr->merge(['reference' => 'Reference', 'label' => 'New label',
                'main' => true], $this->dao);
        $struct = $curr->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/Reference'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }

    public function testPatchRefUpdate() {
        $curr = new Currency();
        $curr->merge(['reference' => 'New ref', 'label' => 'New label',
                'main' => true], $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/Reference'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCurr = $this->dao->search(Currency::class);
        $this->assertEquals(1, count($dbCurr));
        $dbCurr = $this->dao->readSnapshot(Currency::class,
                $dbCurr[0]->getId());
        $this->assertEquals('New ref', $dbCurr->getReference());
    }

    public function testPatchRefExisting() {
        $curr2 = new Currency();
        $curr2->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $this->dao->write($curr2);
        $this->dao->commit();
        $curr = new Currency();
        $curr->merge(['reference' => 'New ref', 'label' => 'New label',
                'main' => true], $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/Reference'));
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
        $this->assertEquals(Currency::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('New ref', $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }

    public function testPatchNotFound() {
        $curr = new Currency();
        $curr->merge(['reference' => 'New ref', 'label' => 'New label',
                'main' => true], $this->dao);
        $struct = $curr->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/currency/notFound'));
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
        $this->assertEquals(Currency::class, $jsResp['class']);
        $this->assertEquals('notFound', $jsResp['key']['reference']);
    }
}
