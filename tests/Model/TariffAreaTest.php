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
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\TariffAreaPrice;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class TariffAreaTest extends TestCase
{
    protected function setUp(): void {
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
        $this->prd->setReference('prd');
        $this->prd->setLabel('Product');
        $this->prd->setCategory($this->cat);
        $this->prd->setTax($this->tax);
        $this->prd->setPriceSell(1.0);
        $this->prd2 = new Product();
        $this->prd2->setReference('prd2');
        $this->prd2->setLabel('Product2');
        $this->prd2->setCategory($this->cat);
        $this->prd2->setTax($this->tax);
        $this->prd2->setPriceSell(2.0);
        $this->prd3 = new Product();
        $this->prd3->setReference('prd3');
        $this->prd3->setLabel('Product');
        $this->prd3->setCategory($this->cat);
        $this->prd3->setTax($this->tax);
        $this->prd3->setPriceSell(3.0);
        $this->dao->write($this->prd);
        $this->dao->write($this->prd2);
        $this->dao->write($this->prd3);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $prices = $this->dao->search(TariffAreaPrice::class);
        foreach($prices as $record) {
            $this->dao->delete($record);
        }
        $ta = $this->dao->search(TariffArea::class);
        foreach($ta as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->prd);
        $this->dao->delete($this->prd2);
        $this->dao->delete($this->prd3);
        $this->dao->delete($this->cat);
        $this->dao->delete($this->tax);
        $this->dao->commit();
        $this->dao->close();
    }

    public function testToStructEmpty() {
        $ta = new TariffArea();
        $ta->setReference('area');
        $ta->setLabel('Area');
        $struct = $ta->toStruct();
        $this->assertEquals('area', $struct['reference']);
        $this->assertEquals('Area', $struct['label']);
        $this->assertTrue(is_array($struct['prices']));
        $this->assertEquals(0, count($struct['prices']));
    }

    /** @depends testToStructEmpty */
    public function testToStructPrices() {
        $ta = new TariffArea();
        $ta->setReference('area');
        $ta->setLabel('Area');
        $price = new TariffAreaPrice();
        $price->setProduct($this->prd);
        $price->setTax($this->tax);
        $price->setPrice(0.5);
        $ta->addPrice($price);
        $struct = $ta->toStruct();
        $this->assertEquals('area', $struct['reference']);
        $this->assertEquals('Area', $struct['label']);
        $this->assertTrue(is_array($struct['prices']));
        $this->assertEquals(1, count($struct['prices']));
        $structPrice = $struct['prices'][0];
        $this->assertEquals($this->prd->getId(), $structPrice['product']);
        $this->assertEquals($this->tax->getId(), $structPrice['tax']);
        $this->assertEquals(0.5, $structPrice['price']);
    }

    public function testMergeEmpty() {
        $struct = array('reference' => 'area', 'label' => 'Area');
        $ta = new TariffArea();
        $ta->merge($struct, $this->dao);
        $this->assertEquals('area', $ta->getReference());
        $this->assertEquals('Area', $ta->getLabel());
        $this->assertEquals(0, $ta->getPrices()->count());
    }

    /** @depends testMergeEmpty */
    public function testMergePrices() {
        $struct = array('reference' => 'area', 'label' => 'Area',
                'prices' => array(array('product' => $this->prd->getId(),
                                'tax' => $this->tax->getId(), 'price' => 0.5)));;
        $ta = new TariffArea();
        $ta->merge($struct, $this->dao);
        $this->assertEquals('area', $ta->getReference());
        $this->assertEquals('Area', $ta->getLabel());
        $this->assertEquals(1, $ta->getPrices()->count());
        $price = $ta->getPrices()->get(0);
        $this->assertEquals($this->prd->getId(), $price->getProduct()->getId());
        $this->assertEquals($this->tax->getId(), $price->getTax()->getId());
        $this->assertEquals(0.5, $price->getPrice());
    }

    /** @depends testMergePrices */
    public function testMergeNull() {
        // 3 prices: [0] with alt price, [1] with alt tax,
        // [2] with noting (should be ignored)
        $struct = array('reference' => 'area', 'label' => 'Area',
                'prices' => array(
                                  array('product' => $this->prd->getId(), 'price' => 0.5),
                                  array('product' => $this->prd2->getId(), 'tax' => $this->tax->getId()),
                                  array('product' => $this->prd3->getId())));
        $ta = new TariffArea();
        $ta->merge($struct, $this->dao);
        $this->assertEquals('area', $ta->getReference());
        $this->assertEquals('Area', $ta->getLabel());
        $this->assertEquals(2, $ta->getPrices()->count());
        $price = $ta->getPrices()->get(0);
        $price2 = $ta->getPrices()->get(1);
        $this->assertEquals($this->prd->getId(), $price->getProduct()->getId());
        $this->assertNull($price->getTax());
        $this->assertEquals(0.5, $price->getPrice());
        $this->assertEquals($this->prd2->getId(), $price2->getProduct()->getId());
        $this->assertEquals($this->tax->getId(), $price2->getTax()->getId());
        $this->assertNull($price2->getPrice());
    }
}
