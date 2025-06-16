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

use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeReturn;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpPaymentModeTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $pm;

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
        $this->pm = new PaymentMode();
        $this->pm->setReference('Reference');
        $this->pm->setLabel('Label');
        $this->dao->write($this->pm);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([PaymentMode::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testPostNew() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/paymentmode'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPm = $this->dao->search(PaymentMode::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbPm));
    }

    public function testPostEdit() {
        $this->pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $this->pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/paymentmode'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPm = $this->dao->search(PaymentMode::class);
        $this->assertEquals(1, count($dbPm));
        $this->assertEquals('New ref', $dbPm[0]->getReference());
        $this->assertEquals('New label', $dbPm[0]->getLabel());
    }

    public function testPostRefExisting() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'Reference', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/paymentmode'));
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
        $this->assertEquals(PaymentMode::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('Reference', $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }

    public function testPostWrongReturn() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        $struct['returns'][] = ['minAmount' => 0,
                'returnMode' => $this->pm->getId() + 1];
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/paymentmode'));
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
        $this->assertEquals('AssociationNotFound', $jsResp['constraint']);
        $this->assertEquals(PaymentModeReturn::class, $jsResp['class']);
        $this->assertEquals('returnMode', $jsResp['field']);
        $this->assertEquals($this->pm->getId() + 1, $jsResp['value']);
        $this->assertEquals('New ref', $jsResp['key']['reference']);
    }


    public function testPutOk() {
        $pm = new PaymentMode();
        $pm->setReference('New ref');
        $pm->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        urlencode($pm->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($pm->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPM = $this->dao->search(PaymentMode::class,
                new DAOCondition('reference', '=', $pm->getReference()));
        $this->assertEquals(1, count($dbPM));
    }

    public function testPutId() {
        $pm = new PaymentMode();
        $pm->setReference('New ref');
        $pm->setLabel('New label');
        $json = $pm->toStruct();
        $json['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        urlencode($pm->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }

    public function testPutRefMismatch() {
        $pm = new PaymentMode();
        $pm->setReference('New ref');
        $pm->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/paymentmode/notNewRef'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($pm->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPM = $this->dao->search(PaymentMode::class,
                new DAOCondition('reference', '=', 'notNewRef'));
        $this->assertEquals(1, count($dbPM));
    }

    public function testPutRefExisting() {
        $pm = new PaymentMode();
        $pm->setReference($this->pm->getReference());
        $pm->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        urlencode($this->pm->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($pm->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(PaymentMode::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('Reference', $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }

    public function testPutWrongReturn() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        $struct['returns'][] = ['minAmount' => 0,
                'returnMode' => $this->pm->getId() + 1];
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        urlencode($struct['reference']))));
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
        $this->assertEquals('AssociationNotFound', $jsResp['constraint']);
        $this->assertEquals(PaymentModeReturn::class, $jsResp['class']);
        $this->assertEquals('returnMode', $jsResp['field']);
        $this->assertEquals($this->pm->getId() + 1, $jsResp['value']);
        $this->assertEquals('New ref', $jsResp['key']['reference']);
    }


    public function testPatchOk() {
        $pm = new PaymentMode();
        $pm->setReference($this->pm->getReference());
        $pm->setLabel('New label');
        $struct = $pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPm = $this->dao->search(PaymentMode::class);
        $this->assertEquals(1, count($dbPm));
        $dbPm = $this->dao->readSnapshot(PaymentMode::class,
                $this->pm->getId());
        $this->assertEquals('New label', $dbPm->getLabel());
    }

    public function testPatchId() {
        $pm = new PaymentMode();
        $pm->setReference($this->pm->getReference());
        $pm->setLabel('New label');
        $struct = $pm->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
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

    public function testPatchRefUpdate() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        $this->pm->getReference())));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPm = $this->dao->search(PaymentMode::class);
        $this->assertEquals(1, count($dbPm));
        $dbPm = $this->dao->readSnapshot(PaymentMode::class,
                $dbPm[0]->getId());
        $this->assertEquals('New ref', $dbPm->getReference());
    }

    public function testPatchRefExisting() {
        $pm2 = new PaymentMode();
        $pm2->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $this->dao->write($pm2);
        $this->dao->commit();
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        $this->pm->getReference())));
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
        $this->assertEquals(PaymentMode::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals('New ref', $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }

    public function testPatchNotFound() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => 'New ref', 'label' => 'New label'],
                $this->dao);
        $struct = $pm->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/paymentmode/notFound'));
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
        $this->assertEquals(PaymentMode::class, $jsResp['class']);
        $this->assertEquals('notFound', $jsResp['key']['reference']);
    }

    public function testPatchWrongReturn() {
        $pm = new PaymentMode();
        $pm->merge(['reference' => $this->pm->getReference(),
                'label' => 'New label'], $this->dao);
        $struct = $pm->toStruct();
        $struct['returns'][] = ['minAmount' => 0,
                'returnMode' => $this->pm->getId() + 1];
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/paymentmode/%s',
                        $this->pm->getReference())));
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
        $this->assertEquals('AssociationNotFound', $jsResp['constraint']);
        $this->assertEquals(PaymentModeReturn::class, $jsResp['class']);
        $this->assertEquals('returnMode', $jsResp['field']);
        $this->assertEquals($this->pm->getId() + 1, $jsResp['value']);
        $this->assertEquals('Reference', $jsResp['key']['reference']);
    }
}
