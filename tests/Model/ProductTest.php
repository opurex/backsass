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
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class ProductTest extends TestCase
{
    const PRICE = 18.18181818;
    const EXPECTED_VAT_PRICE = 20.00;
    const EXPECTED_VAT_PRICE_MULT = 2000000.00;
    const EXPECTED_PRICE = 18.18182;
    const EXPECTED_PRICE_MULT = 1818182.00;
    const EXPECTED_TAX_VALUE = 1.81818;
    const EXPECTED_TAX_MULT = 181818.00;
    private $dao;

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
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $all = $this->dao->search(Product::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->cat);
        $this->dao->delete($this->tax);
        $this->dao->commit();
        $this->dao->close();
    }

    public function testSetScaleType() {
        $prd = new Product();
        $prd->setScaleType(Product::SCALE_TYPE_VOLUME);
        $this->assertEquals(Product::SCALE_TYPE_VOLUME, $prd->getScaleType());
        $exceptionThrown = false;
        try {
            $prd->setScaleType(99);
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
            $this->assertEquals('Unknown scaleType', $e->getMessage());
        }
        $this->assertTrue($exceptionThrown, 'Exception not thrown');
    }

    public function testGetTaxedPrice() {
        $prd = new Product();
        $prd->setReference('prd');
        $prd->setLabel('Product');
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setPriceSell(static::PRICE); // VAT price 20.00
        $this->assertTrue(($prd->getTaxedPrice() - static::EXPECTED_VAT_PRICE) < 0.005,
                sprintf('Invalid taxed price %f', $prd->getTaxedPrice()));
    }

    public function testGetTaxValue() {
        $prd = new Product();
        $prd->setReference('prd');
        $prd->setLabel('Product');
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setPriceSell(static::PRICE); // VAT value 1.82
        $this->assertTrue(($prd->getTaxValue() - static::EXPECTED_TAX_VALUE) < 0.005,
                sprintf('Invalid tax value %f', $prd->getTaxValue()));
    }

    public function testPrepay() {
        $prd = new Product();
        $prd->setReference('prd');
        $prd->setLabel('Product');
        $prd->setCategory($this->cat);
        $prd->setTax($this->tax);
        $prd->setPriceSell(static::PRICE);
        $prd->setPrepay(true);
        $struct = $prd->toStruct();
        $this->assertTrue($struct['prepayValue'] - static::PRICE < 0.005);
    }

    public function testBarcode() {
        $prd = new Product();
        $this->assertEquals('', $prd->getBarcode());
        $prd->setBarcode(null);
        $this->assertEquals('', $prd->getBarcode());
        $prd->setBarcode('123');
        $this->assertEquals('123', $prd->getBarcode());
        $struct = ['reference' => 'ref', 'label' => 'product',
                'barcode' => null,
                'category' => $this->cat->getId(), 'tax' => $this->tax->getId(),
                'priceBuy' => null, 'priceSell' => 10.0, 'visible' => true,
                'scaled' => false, 'scaleType' => Product::SCALE_TYPE_NONE,
                'scaleValue' => 1.0, 'dispOrder' => 0,
                'discountEnabled' => false, 'discountRate' => 0.0,
                'prepay' => false, 'composition' => false];
        $prd2 = new Product();
        $prd2->merge($struct, $this->dao);
        $this->assertEquals('', $prd2->getBarcode());
        $struct['barcode'] = '123';
        $prd2->merge($struct, $this->dao);
        $this->assertEquals('123', $prd2->getBarcode());
    }
}
