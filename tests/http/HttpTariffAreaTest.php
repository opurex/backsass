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

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\TariffAreaPrice;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpTariffAreaTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $cat;
    private $tax;
    private $prd;
    private $prd2;

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
        $this->cat->setReference('category');
        $this->cat->setLabel('Category');
        $this->dao->write($this->cat);
        $this->tax= new Tax();
        $this->tax->setLabel('VAT');
        $this->tax->setRate(0.1);
        $this->dao->write($this->tax);
        $this->prd = new Product();
        $this->prd->setReference('product');
        $this->prd->setLabel('Product');
        $this->prd->setCategory($this->cat);
        $this->prd->setTax($this->tax);
        $this->prd->setPriceSell(1.0);
        $this->prd2 = new Product();
        $this->prd2->setReference('product2');
        $this->prd2->setLabel('Product2');
        $this->prd2->setCategory($this->cat);
        $this->prd2->setTax($this->tax);
        $this->prd2->setPriceSell(0.5);
        $this->dao->write($this->prd);
        $this->dao->write($this->prd2);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([TariffArea::class, Product::class, Tax::class,
                Category::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testPutOk() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $struct = $ta->toStruct();
        unset($struct['id']);
        unset($struct['prices'][0]['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/tariffarea/%s', urlencode($ta->getReference()))));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbTA = $this->dao->search(TariffArea::class,
                new DAOCondition('reference', '=', $ta->getReference()));
        $this->assertEquals(1, count($dbTA));
    }

    public function testPutId() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $struct = $ta->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/tariffarea/%s', urlencode($ta->getReference()))));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }

    public function testPutRefMismatch() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $struct = $ta->toStruct();
        unset($struct['id']);
        unset($struct['prices'][0]['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/tariffarea/notRef'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbTA = $this->dao->search(TariffArea::class,
                new DAOCondition('reference', '=', 'notRef'));
        $this->assertEquals(1, count($dbTA));
    }

    public function testPutRefExisting() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $this->dao->write($ta);
        $this->dao->commit();
        $ta2 = new TariffArea();
        $ta2->setReference('ta');
        $ta2->setLabel('Other area');
        $struct = $ta2->toStruct();
        unset($struct['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/tariffarea/%s', urlencode($ta2->getReference()))));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }


    public function testPostNew() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $struct = $ta->toStruct();
        unset($struct['id']);
        unset($struct['prices'][0]['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/tariffarea'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbTa = $this->dao->search(TariffArea::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbTa));
    }

    public function testPostUpdate() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $taPrice2 = new TariffAreaPrice();
        $taPrice2->setPrice(0.2);
        $taPrice2->setProduct($this->prd2);
        $ta->addPrice($taPrice2);
        $this->dao->write($ta);
        $this->dao->commit();
        $struct = $ta->toStruct();
        $struct['label'] = 'Edited';
        array_splice($struct['prices'], 0, 1);
        $struct['prices'][0]['price'] = 0.1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/tariffarea'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $read = $this->dao->readSnapshot(TariffArea::class, $ta->getId());
        $this->assertEquals(1, count($read->getPrices()));
        $this->assertEquals('Edited', $read->getLabel());
        $this->assertEquals(0.1, $read->getPrices()->get(0)->getPrice());
    }


    public function testPatchOk() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $taPrice2 = new TariffAreaPrice();
        $taPrice2->setPrice(0.2);
        $taPrice2->setProduct($this->prd2);
        $ta->addPrice($taPrice2);
        $this->dao->write($ta);
        $this->dao->commit();
        $struct = $ta->toStruct();
        $struct['label'] = 'Edited';
        array_splice($struct['prices'], 0, 1);
        $struct['prices'][0]['price'] = 0.1;
        unset($struct['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/tariffarea/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $read = $this->dao->readSnapshot(TariffArea::class, $ta->getId());
        $this->assertEquals(1, count($read->getPrices()));
        $this->assertEquals('Edited', $read->getLabel());
        $this->assertEquals(0.1, $read->getPrices()->get(0)->getPrice());
    }

    public function testPatchId() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $taPrice2 = new TariffAreaPrice();
        $taPrice2->setPrice(0.2);
        $taPrice2->setProduct($this->prd2);
        $ta->addPrice($taPrice2);
        $this->dao->write($ta);
        $this->dao->commit();
        $struct = $ta->toStruct();
        $struct['label'] = 'Edited';
        array_splice($struct['prices'], 0, 1);
        $struct['prices'][0]['price'] = 0.1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/tariffarea/%s',
                        urlencode($struct['reference']))));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->markTestIncomplete('Test response message, not available easily with curl');
    }

    public function testPatchNotFound() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $taPrice2 = new TariffAreaPrice();
        $taPrice2->setPrice(0.2);
        $taPrice2->setProduct($this->prd2);
        $ta->addPrice($taPrice2);
        $struct = $ta->toStruct();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/tariffarea/nope'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(TariffArea::class, $jsResp['class']);
        $this->assertEquals('nope', $jsResp['key']['reference']);
    }


    public function testDelete() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Tariff area');
        $taPrice = new TariffAreaPrice();
        $taPrice->setPrice(0.5);
        $taPrice->setProduct($this->prd);
        $ta->addPrice($taPrice);
        $this->dao->write($ta);
        $this->dao->commit();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/tariffarea/%s', urlencode($ta->getReference()))));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbTA = $this->dao->search(TariffArea::class);
        $this->assertEquals(0, count($dbTA));
    }

    public function testDeleteNotFound() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/tariffarea/nothing'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(TariffArea::class, $jsResp['class']);
        $this->assertEquals('nothing', $jsResp['key']['reference']);
    }
}
