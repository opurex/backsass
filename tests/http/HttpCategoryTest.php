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
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpCategoryTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $cat;

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
        $this->cat = new Category();
        $this->cat->setReference('Reference');
        $this->cat->setLabel('Label');
        $this->dao->write($this->cat);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([Category::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testPostNew() {
        $cat = new Category();
        $cat->setReference('ref');
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCat = $this->dao->search(Category::class,
                new DAOCondition('reference', '=', $cat->getReference()));
        $this->assertEquals(1, count($dbCat));
    }

    public function testPostInvalidField() {
        $cat = new Category();
        $cat->setReference('ref');
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        $struct['dispOrder'] = 'NaN';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $err = json_decode($resp, true);
        $this->assertNotEquals(false, $err);
        $this->assertEquals('InvalidField', $err['error']);
        $this->assertEquals('Integer', $err['constraint']);
        $this->assertEquals('dispOrder', $err['field']);
        $this->assertEquals('NaN', $err['value']);
        $dbCat = $this->dao->search(Category::class,
                new DAOCondition('reference', '=', $cat->getReference()));
        $this->assertEquals(0, count($dbCat));
    }

    public function testPostUpdate() {
        $cat = new Category();
        $cat->setReference('ref');
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        $struct['id'] = $this->cat->getId();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCat = $this->dao->readSnapshot(Category::class, $this->cat->getId());
        $this->assertNotNull($dbCat);
        $this->assertEquals('ref', $dbCat->getReference());
        $this->assertEquals('category', $dbCat->getLabel());
    }

    public function testPostRefExisting() {
        $cat = new Category();
        $cat->setReference($this->cat->getReference());
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
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
        $this->assertEquals(Category::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($cat->getReference(), $jsResp['value']);
        $this->assertEquals($cat->getReference(), $jsResp['key']['reference']);
    }

    public function testPostInvalidParent() {
        $cat = new Category();
        $cat->setReference($this->cat->getReference());
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        $struct['id'] = $this->cat->getId();
        $struct['parent'] = $this->cat->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
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
        $this->assertEquals(Category::class, $jsResp['class']);
        $this->assertEquals('parent', $jsResp['field']);
        $this->assertEquals($this->cat->getId() + 1, $jsResp['value']);
        $this->assertEquals($cat->getReference(), $jsResp['key']['reference']);
    }


    public function testPutOk() {
        $cat = new Category();
        $cat->setReference('New ref');
        $cat->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
                        urlencode($cat->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($cat->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCat = $this->dao->search(Category::class,
                new DAOCondition('reference', '=', $cat->getReference()));
        $this->assertEquals(1, count($dbCat));
    }

    public function testPutId() {
        $cat = new Category();
        $cat->setReference('New ref');
        $cat->setLabel('New label');
        $json = $cat->toStruct();
        $json['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s', urlencode($cat->getReference()))));
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
        $cat = new Category();
        $cat->setReference('New ref');
        $cat->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category/notNewRef'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($cat->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCat = $this->dao->search(Category::class,
                new DAOCondition('reference', '=', 'notNewRef'));
        $this->assertEquals(1, count($dbCat));
    }

    public function testPutRefExisting() {
        $cat = new Category();
        $cat->setReference('Reference');
        $cat->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
                        urlencode($this->cat->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($cat->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(Category::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($cat->getReference(), $jsResp['value']);
        $this->assertEquals($cat->getReference(), $jsResp['key']['reference']);
    }

    public function testPutInvalidParent() {
        $cat = new Category();
        $cat->setReference('New ref');
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        $struct['parent'] = $this->cat->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
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
        $this->assertEquals(Category::class, $jsResp['class']);
        $this->assertEquals('parent', $jsResp['field']);
        $this->assertEquals($this->cat->getId() + 1, $jsResp['value']);
        $this->assertEquals($cat->getReference(), $jsResp['key']['reference']);
    }


    public function testPatchOk() {
        $cat = new Category();
        $cat->setReference($this->cat->getReference());
        $cat->setLabel('New label');
        $struct = $cat->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCat = $this->dao->readSnapshot(Category::class, $this->cat->getId());
        $this->assertNotNull($dbCat);
        $this->assertEquals('New label', $dbCat->getLabel());
    }

    public function testPatchId() {
        $cat = new Category();
        $cat->setReference($this->cat->getReference());
        $cat->setLabel('New label');
        $struct = $cat->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
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

    public function testPatchRefMismatch() {
        $cat = new Category();
        $cat->setReference('New ref');
        $cat->setLabel('New label');
        $struct = $cat->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
                        urlencode($this->cat->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbCat = $this->dao->readSnapshot(Category::class, $this->cat->getId());
        $this->assertEquals('New ref', $dbCat->getReference());
    }

    public function testPatchNotFound() {
        $cat = new Category();
        $cat->setReference('Reference');
        $cat->setLabel('New label');
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/category/notfound'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($cat->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(Category::class, $jsResp['class']);
        $this->assertEquals('notfound', $jsResp['key']['reference']);
    }

    public function testPatchInvalidParent() {
        $cat = new Category();
        $cat->setReference($this->cat->getReference());
        $cat->setLabel('category');
        $struct = $cat->toStruct();
        $struct['parent'] = $this->cat->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/category/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
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
        $this->assertEquals(Category::class, $jsResp['class']);
        $this->assertEquals('parent', $jsResp['field']);
        $this->assertEquals($this->cat->getId() + 1, $jsResp['value']);
        $this->assertEquals($cat->getReference(), $jsResp['key']['reference']);
    }
}
