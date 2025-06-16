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
use \Pasteque\Server\Model\CompositionGroup;
use \Pasteque\Server\Model\CompositionProduct;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpProductTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $cat;
    private $tax;

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
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([Product::class, Tax::class, Category::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }


    private function defaultPrd() {
        $prd = new Product();
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10.0);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        return $prd;
    }

    private function defaultPrdStruct() {
        $prd = $this->defaultPrd();
        return $prd->toStruct();
    }

    public function testPostNew() {
        $struct = $this->defaultPrdStruct();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/product'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->search(Product::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(1, count($dbPrd));
    }

    public function testPostUpdate() {
        $prd = $this->defaultPrd();
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        $struct['reference'] = 'edited ref';
        $struct['label'] = 'edited';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/product'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->readSnapshot(Product::class, $prd->getId());
        $this->assertNotNull($dbPrd);
        $this->assertEquals('edited ref', $dbPrd->getReference());
        $this->assertEquals('edited', $dbPrd->getLabel());
    }

    public function testPostRefExisting() {
        $prd = $this->defaultPrd();
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        unset($struct['id']);
        $struct['label'] = 'edited';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/product'));
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
        $this->assertEquals(Product::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($prd->getReference(), $jsResp['value']);
        $this->assertEquals($prd->getReference(), $jsResp['key']['reference']);
    }

    public function testPostInvalidTax() {
        $struct = $this->defaultPrdStruct();
        $struct['tax'] = $this->tax->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/product'));
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
        $this->assertEquals(Product::class, $jsResp['class']);
        $this->assertEquals('tax', $jsResp['field']);
        $this->assertEquals($this->tax->getId() + 1, $jsResp['value']);
        $this->assertEquals($struct['reference'], $jsResp['key']['reference']);
    }

    public function testPostMissingRequiredField() {
        $struct = $this->defaultPrdStruct();
        unset($struct['label']);
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/product'));
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
        $this->assertEquals('NotNull', $jsResp['constraint']);
        $this->assertEquals('label', $jsResp['field']);
        $this->assertEquals(null, $jsResp['value']);
        $dbPrd = $this->dao->search(Product::class,
                new DAOCondition('reference', '=', $struct['reference']));
        $this->assertEquals(0, count($dbPrd));
    }


    public function testPutOk() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($prd->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($prd->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->search(Product::class,
                new DAOCondition('reference', '=', $prd->getReference()));
        $this->assertEquals(1, count($dbPrd));
    }

    /** @depends testPutOk */
    public function testPutCompo() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $this->dao->write($prd);
        $this->dao->commit();
        $compo = new Product();
        $compo->setCategory($this->cat);
        $compo->setTax($this->tax);
        $compo->setReference('compo');
        $compo->setLabel('Compo');
        $compo->setPriceSell(15);
        $compo->setComposition(true);
        $grp = new CompositionGroup();
        $grp->setLabel('Group');
        $grpPrd = new CompositionProduct();
        $grpPrd->setProduct($prd);
        $grp->addCompositionProduct($grpPrd);
        $compo->addCompositionGroup($grp);
        $struct = $compo->toStruct();
        unset($struct['id']);
        unset($struct['compositionGroups'][0]['id']);
        unset($struct['compositionGroups'][0]['compositionProducts'][0]['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s', urlencode($compo->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->search(Product::class,
                new DAOCondition('reference', '=', $compo->getReference()));
        $this->assertEquals(1, count($dbPrd));
    }

    public function testPutId() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $json = $prd->toStruct();
        $json['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($prd->getReference()))));
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
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/product/notRef'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($prd->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->search(Product::class,
                new DAOCondition('reference', '=', 'notRef'));
        $this->assertEquals(1, count($dbPrd));
    }

    public function testPutRefExisting() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $this->dao->write($prd);
        $this->dao->commit();
        $prd2 = new Product();
        $prd2->setCategory($this->cat);
        $prd2->setTax($this->tax);
        $prd2->setReference('ref');
        $prd2->setLabel('other label');
        $prd2->setPriceSell(10);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($prd2->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($prd2->toStruct()));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(Product::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($prd2->getReference(), $jsResp['value']);
        $this->assertEquals($prd2->getReference(), $jsResp['key']['reference']);
    }

    public function testPutInvalidTax() {
        $struct = $this->defaultPrdStruct();
        $struct['tax'] = $this->tax->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
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
        $this->assertEquals(Product::class, $jsResp['class']);
        $this->assertEquals('tax', $jsResp['field']);
        $this->assertEquals($this->tax->getId() + 1, $jsResp['value']);
        $this->assertEquals($struct['reference'], $jsResp['key']['reference']);
    }


    public function testPatchOk() {
        $prd = $this->defaultPrd();;
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        unset($struct['id']);
        $struct['label'] = 'edited';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($prd->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->readSnapshot(Product::class, $prd->getId());
        $this->assertEquals('edited', $dbPrd->getLabel());
    }

    /** @depends testPatchOk */
    public function testPatchCompo() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $this->dao->write($prd);
        $this->dao->commit();
        $compo = new Product();
        $compo->setCategory($this->cat);
        $compo->setTax($this->tax);
        $compo->setReference('compo');
        $compo->setLabel('Compo');
        $compo->setPriceSell(15);
        $compo->setComposition(true);
        $grp = new CompositionGroup();
        $grp->setLabel('Group');
        $grpPrd = new CompositionProduct();
        $grpPrd->setProduct($prd);
        $grp->addCompositionProduct($grpPrd);
        $compo->addCompositionGroup($grp);
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $compo->toStruct();
        unset($struct['id']);
        unset($struct['compositionGroups'][0]['id']);
        unset($struct['compositionGroups'][0]['compositionProducts'][0]['id']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($compo->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->search(Product::class,
                new DAOCondition('reference', '=', $compo->getReference()));
        $this->assertEquals(1, count($dbPrd));
    }

    public function testPatchId() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        $struct['id'] = 1;
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($prd->getReference()))));
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
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        unset($struct['id']);
        $struct['reference'] = 'New ref';
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        $prd->getReference())));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbPrd = $this->dao->search(Product::class);
        $this->assertEquals(1, count($dbPrd));
        $dbPrd = $this->dao->readSnapshot(Product::class, $prd->getId());
        $this->assertEquals('New ref', $dbPrd->getReference());
    }

    public function testPatchRefExisting() {
        $prd = new Product();
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setReference('ref');
        $prd->setLabel('label');
        $prd->setPriceSell(10);
        $this->dao->write($prd);
        $prd2 = new Product();
        $prd2->setCategory($this->cat);
        $prd2->setTax($this->tax);
        $prd2->setReference('ref2');
        $prd2->setLabel('other label');
        $prd2->setPriceSell(10);
        $this->dao->write($prd2);
        $this->dao->commit();
        $struct = $prd->toStruct();
        unset($struct['id']);
        $struct['reference'] = $prd2->getReference();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
                        urlencode($prd->getReference()))));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('UniqueValue', $jsResp['constraint']);
        $this->assertEquals(Product::class, $jsResp['class']);
        $this->assertEquals('reference', $jsResp['field']);
        $this->assertEquals($prd2->getReference(), $jsResp['value']);
        $this->assertEquals($prd->getReference(), $jsResp['key']['reference']);
    }

    public function testPatchInvalidTax() {
        $prd = $this->defaultPrd();
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        unset($struct['id']);
        $struct['tax'] = $this->tax->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/product/%s',
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
        $this->assertEquals(Product::class, $jsResp['class']);
        $this->assertEquals('tax', $jsResp['field']);
        $this->assertEquals($this->tax->getId() + 1, $jsResp['value']);
        $this->assertEquals($struct['reference'], $jsResp['key']['reference']);
    }
}
