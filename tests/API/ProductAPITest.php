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

use \Pasteque\Server\API\ProductAPI;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\CompositionGroup;
use \Pasteque\Server\Model\CompositionProduct;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class ProductAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new ProductAPI($this->dao);
        $this->cat = new Category();
        $this->cat->setReference('category');
        $this->cat->setLabel('Category');
        $this->cat2 = new Category();
        $this->cat2->setReference('category2');
        $this->cat2->setLabel('Category2');
        $this->dao->write($this->cat);
        $this->dao->write($this->cat2);
        $this->tax= new Tax();
        $this->tax->setLabel('VAT');
        $this->tax->setRate(0.1);
        $this->dao->write($this->tax);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $cmpPrds = $this->dao->search(CompositionProduct::class);
        foreach ($cmpPrds as $record) {
            $this->dao->delete($record);
        }
        $cmpGrps = $this->dao->search(CompositionGroup::class);
        foreach ($cmpGrps as $record) {
            $this->dao->delete($record);
        }
        $all = $this->dao->search(Product::class);
        $ids = array();
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->cat);
        $this->dao->delete($this->cat2);
        $this->dao->delete($this->tax);
        $this->dao->commit();
        $this->dao->close();
    }

    /** Get all, get invisible ones. */
    public function testGetAll() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setDispOrder(0);
        $archive = new Product();
        $archive->setReference('Archive');
        $archive->setLabel('Archive');
        $archive->setPriceSell(3);
        $archive->setCategory($this->cat);
        $archive->setTax($this->tax);
        $archive->setVisible(false);
        $archive->setDispOrder(1);
        $this->dao->write($prd);
        $this->dao->write($archive);
        $this->dao->commit();
        $read = $this->api->getAll();
        $this->assertEquals(2, count($read));
        $this->assertEquals($prd->getId(), $read[0]->getId());
        $this->assertEquals($archive->getId(), $read[1]->getId());
    }

    /** Get all visible, ignore archives. */
    public function testGetAllVisible() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $archive = new Product();
        $archive->setReference('Archive');
        $archive->setLabel('Archive');
        $archive->setPriceSell(3);
        $archive->setCategory($this->cat);
        $archive->setTax($this->tax);
        $archive->setVisible(false);
        $this->dao->write($prd);
        $this->dao->write($archive);
        $this->dao->commit();
        $read = $this->api->getAllVisible();
        $this->assertEquals(1, count($read));
        $this->assertEquals($prd->getId(), $read[0]->getId());
    }

    /** Get all invisible, ignore visible. */
    public function testGetArchive() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $archive = new Product();
        $archive->setReference('Archive');
        $archive->setLabel('Archive');
        $archive->setPriceSell(3);
        $archive->setCategory($this->cat);
        $archive->setTax($this->tax);
        $archive->setVisible(false);
        $this->dao->write($prd);
        $this->dao->write($archive);
        $this->dao->commit();
        $read = $this->api->getArchive();
        $this->assertEquals(1, count($read));
        $this->assertEquals($archive->getId(), $read[0]->getId());
    }

    public function testGetFromCategoryObject() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd2 = new Product();
        $prd2->setReference('Archive');
        $prd2->setLabel('Archive');
        $prd2->setPriceSell(3);
        $prd2->setCategory($this->cat2);
        $prd2->setTax($this->tax);
        $this->dao->write($prd);
        $this->dao->write($prd2);
        $this->dao->commit();
        $read = $this->api->getFromCategory($this->cat2);
        $this->assertEquals(1, count($read));
        $this->assertEquals($prd2->getId(), $read[0]->getId());
    }

    /** @depends testGetFromCategoryObject */
    public function testGetFromCategoryIntId() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd2 = new Product();
        $prd2->setReference('Archive');
        $prd2->setLabel('Archive');
        $prd2->setPriceSell(3);
        $prd2->setCategory($this->cat2);
        $prd2->setTax($this->tax);
        $this->dao->write($prd);
        $this->dao->write($prd2);
        $this->dao->commit();
        $read = $this->api->getFromCategory($this->cat2->getId());
        $this->assertEquals(1, count($read));
        $this->assertEquals($prd2->getId(), $read[0]->getId());
    }

    /** @depends testGetFromCategoryObject */
    public function testGetFromCategoryStringId() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd2 = new Product();
        $prd2->setReference('Archive');
        $prd2->setLabel('Archive');
        $prd2->setPriceSell(3);
        $prd2->setCategory($this->cat2);
        $prd2->setTax($this->tax);
        $this->dao->write($prd);
        $this->dao->write($prd2);
        $this->dao->commit();
        $read = $this->api->getFromCategory(strval($this->cat2->getId()));
        $this->assertEquals(1, count($read));
        $this->assertEquals($prd2->getId(), $read[0]->getId());
    }

    /** Pass pure garbage to the function. */
    public function testGetFromCategoryGarbage() {
        $this->expectException(\InvalidArgumentException::class);
        $this->api->getFromCategory(array('I', 'am', 'Garbage'));
    }

    /* Pass an other DoctrineModel to the API. */
    public function testGetFromCategoryOtherGarbage() {
        $this->expectException(\InvalidArgumentException::class);
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $this->dao->write($prd);
        $this->dao->commit();
        $this->api->getFromCategory($prd);
    }

    public function testGetByCode() {
        $barcode = '5901234123457';
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setBarcode($barcode);
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd2 = new Product();
        $prd2->setReference('Archive');
        $prd2->setLabel('Archive');
        $prd2->setPriceSell(3);
        $prd2->setCategory($this->cat2);
        $prd2->setTax($this->tax);
        $this->dao->write($prd);
        $this->dao->commit();
        $read = $this->api->getByCode($barcode);
        $this->assertNotNull($read);
        $this->assertEquals($prd->getId(), $read->getId());        
    }

    public function testGetByCodeNull() {
        $barcode = '5901234123457';
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setBarcode($barcode);
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd2 = new Product();
        $prd2->setReference('Archive');
        $prd2->setLabel('Archive');
        $prd2->setPriceSell(3);
        $prd2->setCategory($this->cat2);
        $prd2->setTax($this->tax);
        $this->dao->write($prd);
        $this->dao->commit();
        $read = $this->api->getByCode('123123');
        $this->assertNull($read);
    }

    public function testComposition() {
        $prd = new Product();
        $prd->setReference('Ref');
        $prd->setLabel('Label');
        $prd->setPriceSell(1);
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $grp = new CompositionGroup();
        $grp->setLabel('group');
        $grp->setDispOrder(1);
        $prd->setComposition(true);
        $prd->addCompositionGroup($grp);
        $prd2 = new Product();
        $prd2->setReference('Archive');
        $prd2->setLabel('Archive');
        $prd2->setPriceSell(3);
        $prd2->setCategory($this->cat2);
        $prd2->setTax($this->tax);
        $this->dao->write($prd2);
        $this->dao->commit();
        $grpPrd = new CompositionProduct();
        $grpPrd->setProduct($prd2);
        $grp->addCompositionProduct($grpPrd);
        $this->dao->write($prd);
        $this->dao->commit();
        $struct = $prd->toStruct();
        $this->assertEquals(1, count($struct['compositionGroups']));
        $structGrp = $struct['compositionGroups'][0];
        $this->assertEquals('group', $structGrp['label']);
        $this->assertEquals(1, count($structGrp['compositionProducts']));
        $structPrd = $structGrp['compositionProducts'][0];
        $this->assertEquals($prd2->getId(), $structPrd['product']);
    }
}
