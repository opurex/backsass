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

use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeReturn;
use \Pasteque\Server\Model\PaymentModeValue;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

/** PaymentMode and DoctrineModel association tests. */
class PaymentModeTest extends TestCase
{
    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $returns = $this->dao->search(PaymentModeReturn::class);
        foreach($returns as $record) {
            $this->dao->delete($record);
        }
        $values = $this->dao->search(PaymentModeValue::class);
        foreach($values as $record) {
            $this->dao->delete($record);
        }
        $pm = $this->dao->search(PaymentMode::class);
        foreach($pm as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testUsesPrepay() {
        $pm = new PaymentMode();
        $pm->setType(PaymentMode::CUST_PREPAID);
        $this->assertTrue($pm->usesPrepay());
        $pm->setType(PaymentMode::TYPE_DEFAULT);
        $this->assertFalse($pm->usesPrepay());
        $pm->setType(PaymentMode::CUST_ASSIGNED);
        $this->assertFalse($pm->usesPrepay());
        $pm->setType(PaymentMode::CUST_DEBT);
        $this->assertFalse($pm->usesPrepay());
    }

    public function testUsesDebt() {
        $pm = new PaymentMode();
        $pm->setType(PaymentMode::CUST_DEBT);
        $this->assertTrue($pm->usesDebt());
        $pm->setType(PaymentMode::CUST_PREPAID);
        $this->assertFalse($pm->usesDebt());
        $pm->setType(PaymentMode::TYPE_DEFAULT);
        $this->assertFalse($pm->usesDebt());
        $pm->setType(PaymentMode::CUST_ASSIGNED);
        $this->assertFalse($pm->usesDebt());
    }

    public function testToStructEmpty() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $pm->setType(PaymentMode::CUST_DEBT);
        $struct = $pm->toStruct();
        $this->assertEquals('test', $struct['reference']);
        $this->assertEquals('Test mode', $struct['label']);
        $this->assertEquals(PaymentMode::CUST_DEBT, $struct['type']);
        $this->assertTrue($struct['visible']);
        $this->assertEquals(0, $struct['dispOrder']);
    }

    /** @depends testToStructEmpty */
    public function testToStructValues() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $value = new PaymentModeValue();
        $value->setValue(1);
        $value2 = new PaymentModeValue();
        $value2->setValue(2);
        $pm->addValue($value);
        $pm->addValue($value2);
        $struct = $pm->toStruct();
        $this->assertEquals('test', $struct['reference']);
        $this->assertEquals('Test mode', $struct['label']);
        $this->assertTrue(is_array($struct['values']));
        $this->assertEquals(2, count($struct['values']));
        $structValue = $struct['values'][0];
        $structValue2 = $struct['values'][1];
        $this->assertEquals(1, $structValue['value']);
        $this->assertEquals(2, $structValue2['value']);
    }

    /** @depends testToStructEmpty */
    public function testToStructReturn() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $ret = new PaymentModeReturn();
        $ret2 = new PaymentModeReturn();
        $ret2->setMinAmount(10.0);
        $pm->addReturn($ret);
        $pm->addReturn($ret2);
        $struct = $pm->toStruct();
        $this->assertEquals('test', $struct['reference']);
        $this->assertEquals('Test mode', $struct['label']);
        $this->assertTrue(is_array($struct['returns']));
        $this->assertEquals(2, count($struct['returns']));
        $structRet = $struct['returns'][0];
        $structRet2 = $struct['returns'][1];
        $this->assertEquals(0.0, $structRet['minAmount']);
        $this->assertEquals(10.0, $structRet2['minAmount']);
    }

    public function testMergeEmpty() {
        $struct = array('reference' => 'test', 'label' => 'Test mode');
        $pm = new PaymentMode();
        $pm->merge($struct, $this->dao);
        $this->assertEquals('test', $pm->getReference());
        $this->assertEquals('Test mode', $pm->getLabel());
        $this->assertEquals(0, $pm->getValues()->count());
        $this->assertEquals(0, $pm->getReturns()->count());
    }

    /** @depends testMergeEmpty */
    public function testMergeValues() {
        $struct = array('reference' => 'test', 'label' => 'Test mode',
                'values' => array(array('value' => 1), array('value' => 2)));
        $pm = new PaymentMode();
        $pm->merge($struct, $this->dao);
        $this->assertEquals('test', $pm->getReference());
        $this->assertEquals('Test mode', $pm->getLabel());
        $this->assertEquals(2, $pm->getValues()->count());
        $value = $pm->getValues()->get(0);
        $value2 = $pm->getValues()->get(1);
        $this->assertEquals(1.0, $value->getValue());
        $this->assertEquals(2.0, $value2->getValue());
    }

    public function testEqualsEmptyArray() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $pm2 = new PaymentMode();
        $pm2->setReference('test');
        $pm2->setLabel('Test mode');
        $this->assertTrue($pm->equals($pm2));
    }

    public function testEqualsArray() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $value = new PaymentModeValue();
        $value->setValue(1);
        $value2 = new PaymentModeValue();
        $value2->setValue(2);
        $pm->addValue($value);
        $pm->addValue($value2);
        $pm2 = new PaymentMode();
        $pm2->setReference('test');
        $pm2->setLabel('Test mode');
        $value3 = new PaymentModeValue();
        $value3->setValue(1);
        $value4 = new PaymentModeValue();
        $value4->setValue(2);
        $pm2->addValue($value3);
        $pm2->addValue($value4);
        $this->assertTrue($pm->equals($pm2));
    }

    public function testEqualsArraySizeMismatch() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $value = new PaymentModeValue();
        $value->setValue(1);
        $value2 = new PaymentModeValue();
        $value2->setValue(2);
        $pm->addValue($value);
        $pm->addValue($value2);
        $pm2 = new PaymentMode();
        $pm2->setReference('test');
        $pm2->setLabel('Test mode');
        $value3 = new PaymentModeValue();
        $value3->setValue(1);
        $pm2->addValue($value3);
        $this->assertFalse($pm->equals($pm2));
    }

    public function testEqualsArrayUnordered() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $value = new PaymentModeValue();
        $value->setValue(1);
        $value2 = new PaymentModeValue();
        $value2->setValue(2);
        $pm->addValue($value);
        $pm->addValue($value2);
        $pm2 = new PaymentMode();
        $pm2->setReference('test');
        $pm2->setLabel('Test mode');
        $value3 = new PaymentModeValue();
        $value3->setValue(1);
        $value4 = new PaymentModeValue();
        $value4->setValue(2);
        $pm2->addValue($value4);
        $pm2->addValue($value3);
        $this->assertFalse($pm->equals($pm2));
    }

    public function testEqualsArrayValueMismatch() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $value = new PaymentModeValue();
        $value->setValue(1);
        $value2 = new PaymentModeValue();
        $value2->setValue(2);
        $pm->addValue($value);
        $pm->addValue($value2);
        $pm2 = new PaymentMode();
        $pm2->setReference('test');
        $pm2->setLabel('Test mode');
        $value3 = new PaymentModeValue();
        $value3->setValue(1);
        $value4 = new PaymentModeValue();
        $value4->setValue(3);
        $pm2->addValue($value3);
        $pm2->addValue($value4);
        $this->assertFalse($pm->equals($pm2));
    }

    public function testEqualsReturnLoop() {
        $pm = new PaymentMode();
        $pm->setReference('test');
        $pm->setLabel('Test mode');
        $ret = new PaymentModeReturn();
        $pm->addReturn($ret);
        $pm2 = new PaymentMode();
        $pm2->setReference('test');
        $pm2->setLabel('Test mode');
        $ret2 = new PaymentModeReturn();
        $pm2->addReturn($ret2);
        $this->assertTrue($pm->equals($pm2));
    }
}
