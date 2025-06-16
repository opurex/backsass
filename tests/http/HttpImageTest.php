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

use \Pasteque\Server\API\ImageAPI;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\Image;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpImageTest extends TestCase
{
    const IMG_PATH = __DIR__ . '/../res/image.png';
    private $curl;
    private static $token;
    private static $imgData;
    private $dao;
    private $cat;

    public static function setUpBeforeClass(): void {
        static::$token = obtainToken();
        static::$imgData = file_get_contents(static::IMG_PATH);
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
        $this->cat->setReference('Category');
        $this->cat->setLabel('Category');
        $this->dao->write($this->cat);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([Image::class, Category::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    private function wrongModelCheck($method, $id = null) {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        if ($id == null) {
            curl_setopt($this->curl, CURLOPT_URL,
                    apiUrl('api/image/cat/default'));
        } else {
            curl_setopt($this->curl, CURLOPT_URL,
                    apiUrl(sprintf('api/image/cat/%d', $id)));
        }
        if ($method == 'PUT' || $method == 'PATCH') {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, static::$imgData);
        }
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('Enum', $jsResp['constraint']);
        $this->assertEquals(Image::class, $jsResp['class']);
        $this->assertEquals('model', $jsResp['field']);
        $this->assertEquals('cat', $jsResp['key']['model']);
        if ($id === null) {
            $this->assertEquals('default', $jsResp['key']['modelId']);
        } else {
            $this->assertEquals($id, $jsResp['key']['modelId']);
        }
        $this->assertEquals('cat', $jsResp['value']);
    }

    public function testGetDefaultWrongModel() {
        $this->wrongModelCheck('GET');
    }

    public function testGetNoImage() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/image/category/%d',
                        $this->cat->getId())));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, static::$imgData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(Image::class, $jsResp['class']);
        $this->assertEquals(Image::MODEL_CATEGORY, $jsResp['key']['model']);
        $this->assertEquals($this->cat->getId(), $jsResp['key']['modelId']);
    }

    public function testGetWrongModel() {
        $this->wrongModelCheck('GET', $this->cat->getId());
    }

    public function testPutOk() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/image/category/%d', $this->cat->getId())));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, static::$imgData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $catSnapshot = $this->dao->readSnapshot(Category::class,
                $this->cat->getId());
        $this->assertTrue($catSnapshot->hasImage());
        $imgSnapshot = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_CATEGORY,
                        'modelId' => $this->cat->getId()]);
        $this->assertNotNull($imgSnapshot);
    }

    public function testPutWrongModel() {
        $this->wrongModelCheck('PUT', $this->cat->getId());
    }

    public function testPutExisting() {
        $img = new Image();
        $img->setModel(Image::MODEL_CATEGORY);
        $img->setModelId($this->cat->getId());
        $img->setImage(static::$imgData);
        $img->setMimeType('image/png');
        $imgApi = new ImageAPI(null, $this->dao);
        $imgApi->write($img);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/image/category/%d', $this->cat->getId())));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, static::$imgData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(Image::class, $jsResp['class']);
        $this->assertEquals('modelId', $jsResp['field']);
        $this->assertEquals($this->cat->getId(), $jsResp['value']);
        $this->assertEquals(Image::MODEL_CATEGORY, $jsResp['key']['model']);
        $this->assertEquals($this->cat->getId(), $jsResp['key']['modelId']);
    }

    public function testPutNotFound() {
        $img = new Image();
        $img->setModel(Image::MODEL_CATEGORY);
        $img->setModelId($this->cat->getId());
        $img->setImage(static::$imgData);
        $img->setMimeType('image/png');
        $imgApi = new ImageAPI(null, $this->dao);
        $imgApi->write($img);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/image/category/%d',
                        $this->cat->getId() + 1)));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, static::$imgData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('AssociationNotFound', $jsResp['constraint']);
        $this->assertEquals(Image::class, $jsResp['class']);
        $this->assertEquals('modelId', $jsResp['field']);
        $this->assertEquals($this->cat->getId() + 1, $jsResp['value']);
        $this->assertEquals(Image::MODEL_CATEGORY, $jsResp['key']['model']);
        $this->assertEquals($this->cat->getId() + 1, $jsResp['key']['modelId']);
    }

    public function testDelete() {
        $img = new Image();
        $img->setModel(Image::MODEL_CATEGORY);
        $img->setModelId($this->cat->getId());
        $img->setImage(static::$imgData);
        $img->setMimeType('image/png');
        $imgApi = new ImageAPI(null, $this->dao);
        $imgApi->write($img);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/image/category/%d', $this->cat->getId())));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(1, $resp);
        $this->assertEquals(null, $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_CATEGORY, 'modelId' => $this->cat->getId()]));
        $cat = $this->dao->readSnapshot(Category::class, $this->cat->getId());
        $this->assertEquals(false, $cat->hasImage());
    }

    public function testDeleteNotFound() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/image/category/%d', $this->cat->getId())));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(0, $resp);
    }

    public function testDeleteWrongModel() {
        $this->wrongModelCheck('DELETE', $this->cat->getId());
    }
}
