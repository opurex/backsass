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

use \Pasteque\Server\API\TariffareaAPI;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\TariffAreaPrice;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class TariffAreaAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new TariffareaAPI($this->dao);
        $this->tax = new Tax();
        $this->tax->merge(['label' => 'tax', 'rate' => 0.1], $this->dao);
        $this->dao->write($this->tax);
        $this->category = new Category();
        $this->category->merge(['reference' => 'category',
                'label' => 'category'], $this->dao);
        $this->dao->write($this->category);
        $this->dao->commit();
        $this->product = new Product();
        $this->product->merge(['reference' => 'product', 'label' => 'product',
                'priceSell' => 1.0, 'category' => $this->category->getId(),
                'tax' => $this->tax->getId()], $this->dao);
        $this->product2 = new Product();
        $this->product2->merge(['reference' => 'product2',
                'label' => 'product2', 'priceSell' => 2.0,
                'category' => $this->category->getId(),
                'tax' => $this->tax->getId()], $this->dao);
        $this->dao->write($this->product);
        $this->dao->write($this->product2);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $prices = $this->dao->search(TariffAreaPrice::class);
        foreach ($prices as $record) {
            $this->dao->delete($record);
        }
        $areas = $this->dao->search(TariffArea::class);
        foreach ($areas as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->product);
        $this->dao->delete($this->product2);
        $this->dao->delete($this->category);
        $this->dao->delete($this->tax);
        $this->dao->commit();
        $this->dao->close();
    }

    /** Create and read an empty TariffArea */
    public function testEmpty() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Area');
        $this->api->write($ta);
        $read = $this->dao->read(TariffArea::class, $ta->getId());
        $this->assertEquals($ta->getReference(), $read->getReference());
        $this->assertEquals($ta->getLabel(), $read->getLabel());
        $this->assertEquals(0, count($read->getPrices()));
    }

    public function testPrice() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Area');
        $price = new TariffAreaPrice();
        $price->setProduct($this->product);
        $price->setPrice(0.5);
        $ta->addPrice($price);
        $this->api->write($ta);
        $read = $this->dao->read(TariffArea::class, $ta->getId());
        $this->assertEquals($ta->getReference(), $read->getReference());
        $this->assertEquals($ta->getLabel(), $read->getLabel());
        $this->assertEquals(1, count($read->getPrices()));
        $this->assertEquals(0.5, $read->getPrices()->get(0)->getPrice());
        $id = $read->getPrices()->get(0)->getId();
        $this->assertEquals(2, count($id));
        $this->assertEquals($ta->getId(), $id['tariffArea']);
        $this->assertEquals($this->product->getId(), $id['product']);
    }

    /** @depends testPrice */
    public function testRemovePrice() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Area');
        $price = new TariffAreaPrice();
        $price->setProduct($this->product);
        $price->setPrice(0.5);
        $ta->addPrice($price);
        $price2 = new TariffAreaPrice();
        $price2->setProduct($this->product2);
        $price2->setPrice(0.2);
        $ta->addPrice($price2);
        $this->api->write($ta);
        $ta->removePrice($price);
        $this->api->write($ta);
        $read = $this->dao->readSnapshot(TariffArea::class, $ta->getId());
        $this->assertEquals($ta->getReference(), $read->getReference());
        $this->assertEquals($ta->getLabel(), $read->getLabel());
        $this->assertEquals(1, count($read->getPrices()));
        $this->assertEquals(0.2, $read->getPrices()->get(0)->getPrice());
    }

    /** @depends testPrice */
    public function testDeleteCascade() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Area');
        $price = new TariffAreaPrice();
        $price->setProduct($this->product);
        $price->setPrice(0.5);
        $ta->addPrice($price);
        $this->dao->write($ta);
        $this->dao->commit();
        $areaId = $ta->getId();
        $priceId = $price->getId();
        $count = $this->api->delete($areaId);
        $this->assertEquals(1, $count);
        $read = $this->dao->read(TariffAreaPrice::class, $priceId);
        $this->assertNull($read);
    }

    /** @depends testDeleteCascade */
    public function testReplace() {
        $ta = new TariffArea();
        $ta->setReference('ta');
        $ta->setLabel('Area');
        $price = new TariffAreaPrice();
        $price->setProduct($this->product);
        $price->setPrice(0.5);
        $ta->addPrice($price);
        $this->dao->write($ta);
        $this->dao->commit();
        $replaceTa = TariffArea::load('ta', $this->dao);
        $replaceTa->merge(['reference' => 'ta', 'label' => 'replaced',
                'prices' => [['product' => $this->product2->getId(),
                        'price' => 0.2]]],
                $this->dao);
        $this->api->write($replaceTa);
        $readTa = $this->dao->readSnapshot(TariffArea::class, $ta->getId());
        $allTa = $this->dao->search(TariffArea::class);
        $this->assertEquals(1, count($allTa),
                'Tariff area was inserted instead of updated');
        $this->assertEquals($ta->getId(), $readTa->getId());
        $this->assertEquals('replaced', $readTa->getLabel());
        $allPrices = $this->dao->search(TariffAreaPrice::class);
        $this->assertEquals(1, count($allPrices),
                            'Prices were inserted instead of replaced');
        $this->assertEquals(1, $readTa->getPrices()->count(),
                'New price was not attached to the updated tariff area');
        $price = $readTa->getPrices()->get(0);
        $this->assertEquals($this->product2->getId(), $price->getProduct()->getId());
        $this->assertEquals(0.2, $price->getPrice());
    }
}
