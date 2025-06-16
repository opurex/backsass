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

use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpResourceTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;

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
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        $all = $this->dao->search(Resource::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testPostNew() {
        $newRes = new Resource();
        $newRes->setLabel('New resource');
        $newRes->setType(Resource::TYPE_TEXT);
        $newRes->setContent('Text content');
        $postData = $newRes->toStruct();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/resource'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbRes = $this->dao->readSnapshot(Resource::class, 'New resource');
        $this->assertNotNull($dbRes);
        $this->assertEquals('Text content', $dbRes->getContent());
    }

    public function testPostUpdate() {
        $res = new Resource();
        $res->setLabel('Test resource');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $this->dao->write($res);
        $this->dao->commit();
        $postData = $res->toStruct();
        $postData['content'] = 'Updated content';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/resource'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbRes = $this->dao->readSnapshot(Resource::class, 'Test resource');
        $this->assertNotNull($dbRes);
        $this->assertEquals('Updated content', $dbRes->getContent());
    }

    public function testPatchOk() {
        $res = new Resource();
        $res->setLabel('Test resource');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $this->dao->write($res);
        $this->dao->commit();
        $struct = $res->toStruct();
        $struct['content'] = 'Updated content';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/resource/%s',
                    urlencode($struct['label']))));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbRes = $this->dao->readSnapshot(Resource::class, 'Test resource');
        $this->assertNotNull($dbRes);
        $this->assertEquals('Updated content', $dbRes->getContent());
    }

    public function testPatchLabelExisting() {
        $res = new Resource();
        $res->setLabel('Test resource');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $this->dao->write($res);
        $res2 = new Resource();
        $res2->setLabel('Res2');
        $res2->setType(Resource::TYPE_TEXT);
        $res2->setContent('Content');
        $this->dao->write($res2);
        $this->dao->commit();
        $struct = $res2->toStruct();
        $struct['label'] = $res->getLabel();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/resource/%s',
                    urlencode($res2->getLabel()))));
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
        $this->assertEquals(Resource::class, $jsResp['class']);
        $this->assertEquals('label', $jsResp['field']);
        $this->assertEquals($res2->getLabel(), $jsResp['key']['label']);
        $this->assertEquals($res->getLabel(), $jsResp['value']);
    }

    public function testPatchNotFound() {
        $res = new Resource();
        $res->setLabel('Test resource');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $postData = $res->toStruct();
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/resource/%s',
                    urlencode($postData['label']))));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(Resource::class, $jsResp['class']);
        $this->assertEquals('Test resource', $jsResp['key']['label']);
    }

    public function testDelete() {
        $res = new Resource();
        $res->setLabel('Printer.Ticket.Header');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $this->dao->write($res);
        $this->dao->commit();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/resource/Printer.Ticket.Header'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbRes = $this->dao->readSnapshot(Resource::class, 'Printer.Ticket.Header');
        $this->assertNull($dbRes);
    }
}
