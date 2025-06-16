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

use \Pasteque\Server\CommonAPI\OptionAPI;
use \Pasteque\Server\Model\Option;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpOptionTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $sysOpt;
    private $opt;

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
        $opt = new Option();
        $opt->setName('opt');
        $opt->setContent('val');
        $sysOpt = new Option();
        $sysOpt->setName('sys');
        $sysOpt->setContent('sysVal');
        $sysOpt->setSystem(true);
        $this->dao->write($opt);
        $this->dao->write($sysOpt);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        $all = $this->dao->search(Option::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetAll() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option/getAll'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $this->assertEquals(1, count($data));
        $this->assertEquals('opt', $data[0]['name']);
        $this->assertEquals('val', $data[0]['content']);
        $this->assertFalse($data[0]['system']);
    }

    public function testGet() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option/opt'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $this->assertEquals('opt', $data['name']);
        $this->assertEquals('val', $data['content']);
        $this->assertFalse($data['system']);
    }

    public function testGetSys() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option/sys'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $this->assertEquals('sys', $data['name']);
        $this->assertEquals('sysVal', $data['content']);
        $this->assertTrue($data['system']);
    }

    public function testGetNone() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option/oops'));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals('null', $resp);
    }

    public function testPostCreate() {
        $opt = new Option();
        $opt->setName('new');
        $opt->setContent('newVal');
        $postData = json_encode($opt->toStruct());
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $newOpt = $this->dao->readSnapshot(Option::class, 'new');
        $this->assertNotNull($newOpt);
        $this->assertEquals('new', $newOpt->getName());
        $this->assertEquals('newVal', $newOpt->getContent());
        $this->assertFalse($newOpt->isSystem());
    }

    public function testPostCreateSys() {
        $opt = new Option();
        $opt->setName('new');
        $opt->setContent('newVal');
        $opt->setSystem(true);
        $postData = json_encode($opt->toStruct());
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidRecord', $jsResp['error']);
        $this->assertEquals('ReadOnly', $jsResp['constraint']);
        $this->assertEquals(Option::class, $jsResp['class']);
        $this->assertEquals('new', $jsResp['key']);
        $newOpt = $this->dao->readSnapshot(Option::class, 'new');
        $this->assertNull($newOpt);
    }

    public function testPostUpdate() {
        $opt = new Option();
        $opt->setName('opt');
        $opt->setContent('newVal');
        $postData = json_encode($opt->toStruct());
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $newOpt = $this->dao->readSnapshot(Option::class, 'opt');
        $this->assertNotNull($newOpt);
        $this->assertEquals('opt', $newOpt->getName());
        $this->assertEquals('newVal', $newOpt->getContent());
        $this->assertFalse($newOpt->isSystem());
    }

    public function testPostUpdateSys() {
        $opt = new Option();
        $opt->setName('sys');
        $opt->setContent('newVal');
        $opt->setSystem(true);
        $postData = json_encode($opt->toStruct());
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidRecord', $jsResp['error']);
        $this->assertEquals('ReadOnly', $jsResp['constraint']);
        $this->assertEquals(Option::class, $jsResp['class']);
        $this->assertEquals('sys', $jsResp['key']);
        $newOpt = $this->dao->readSnapshot(Option::class, 'sys');
        $this->assertNotNull($newOpt);
        $this->assertEquals('sys', $newOpt->getName());
        $this->assertEquals('sysVal', $newOpt->getContent());
        $this->assertTrue($newOpt->isSystem());
    }

    public function testPostUpdateSetSys() {
        $opt = new Option();
        $opt->setName('opt');
        $opt->setContent('newVal');
        $opt->setSystem(true);
        $postData = json_encode($opt->toStruct());
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidRecord', $jsResp['error']);
        $this->assertEquals('ReadOnly', $jsResp['constraint']);
        $this->assertEquals(Option::class, $jsResp['class']);
        $this->assertEquals('opt', $jsResp['key']);
        $newOpt = $this->dao->readSnapshot(Option::class, 'opt');
        $this->assertNotNull($newOpt);
        $this->assertEquals('opt', $newOpt->getName());
        $this->assertEquals('val', $newOpt->getContent());
        $this->assertFalse($newOpt->isSystem()); 
    }

    public function testDelete() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option/opt'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $opt = $this->dao->readSnapshot(Option::class, 'opt');
        $this->assertNull($opt);
    }

    public function testDeleteSys() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/option/sys'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $opt = $this->dao->readSnapshot(Option::class, 'sys');
        $this->assertNotNull($opt);
    }
}
