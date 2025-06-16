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
use \Pasteque\Server\API\PaymentmodeAPI;
use \Pasteque\Server\Model\Image;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeReturn;
use \Pasteque\Server\Model\PaymentModeValue;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class PaymentModeAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new PaymentModeAPI($this->dao);
    }

    protected function tearDown(): void {
        $imgs = $this->dao->search(Image::class);
        foreach ($imgs as $img) {
            $this->dao->delete($img);
        }
        $returns = $this->dao->search(PaymentModeReturn::class);
        foreach ($returns as $record) {
            $this->dao->delete($record);
        }
        $values = $this->dao->search(PaymentModeValue::class);
        foreach ($values as $record) {
            $this->dao->delete($record);
        }
        $modes = $this->dao->search(PaymentMode::class);
        foreach ($modes as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    /** Create and read an empty PaymentMode */
    public function testEmpty() {
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $this->api->write($pm);
        $read = $this->dao->read(PaymentMode::class, $pm->getId());
        $this->assertEquals($pm->getReference(), $read->getReference());
        $this->assertEquals($pm->getLabel(), $read->getLabel());
        $this->assertEquals('', $read->getBackLabel());
        $this->assertEquals(PaymentMode::TYPE_DEFAULT, $read->getType());
        $this->assertEquals(0, count($read->getValues()));
        $this->assertEquals(0, count($read->getReturns()));
        $this->assertEquals(0, $read->getDispOrder());
    }

    /** @depends testEmpty */
    public function testValues() {
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        $this->api->write($pm);
        $read = $this->dao->read(PaymentMode::class, $pm->getId());
        $this->assertEquals($pm->getReference(), $read->getReference());
        $this->assertEquals($pm->getLabel(), $read->getLabel());
        $this->assertEquals(1, count($read->getValues()));
        $this->assertEquals(10.0, $read->getValues()->get(0)->getValue());
        $id = $read->getValues()->get(0)->getId();
        $this->assertEquals(2, count($id));
        $this->assertEquals($pm->getId(), $id['paymentMode']);
        $this->assertEquals(10.0, $id['value']);
    }

    /** @depends testEmpty */
    public function testSelfReturn() {
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $return = new PaymentModeReturn();
        $return->setMinAmount(1.0);
        $return->setReturnMode($pm);
        $pm->addReturn($return);
        $this->api->write($pm);
        $read = $this->dao->read(PaymentMode::class, $pm->getId());
        $this->assertEquals($pm->getReference(), $read->getReference());
        $this->assertEquals($pm->getLabel(), $read->getLabel());
        $this->assertEquals(1, count($read->getReturns()));
        $this->assertEquals(1.0, $read->getReturns()->get(0)->getMinAmount());
        $id = $read->getReturns()->get(0)->getId();
        $this->assertEquals(2, count($id));
        $this->assertEquals($pm->getId(), $id['paymentMode']);
        $this->assertEquals(1.0, $id['minAmount']);
    }

    /** @depends testSelfReturn */
    public function testOtherReturn() {
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $pm2 = new PaymentMode();
        $pm2->setReference('back');
        $pm2->setLabel('Back');
        $return = new PaymentModeReturn();
        $return->setMinAmount(1.0);
        $return->setReturnMode($pm2);
        $pm->addReturn($return);
        $this->api->write(array($pm, $pm2));
        $read = $this->dao->read(PaymentMode::class, $pm->getId());
        $this->assertEquals($pm->getReference(), $read->getReference());
        $this->assertEquals($pm->getLabel(), $read->getLabel());
        $this->assertEquals(1, count($read->getReturns()));
        $this->assertEquals(1.0, $read->getReturns()->get(0)->getMinAmount());
        $readReturn = $read->getReturns()->get(0);
        $id = $readReturn->getId();
        $this->assertEquals(2, count($id));
        $this->assertEquals($pm->getId(), $id['paymentMode']);
        $this->assertEquals(1.0, $id['minAmount']);
        $this->assertEquals($pm2->getId(), $readReturn->getReturnMode()->getId());
    }

    /** @depends testValues
     * @depends testSelfReturn */
    public function testDeleteCascade() {
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $return = new PaymentModeReturn();
        $return->setMinAmount(1.0);
        $return->setReturnMode($pm);
        $pm->addReturn($return);
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        $this->api->write($pm);
        $pmId = $pm->getId();
        $returnId = $return->getId();
        $valueId = $value->getId();
        $count = $this->api->delete($pm->getId());
        $this->assertEquals(1, $count);
        $readValue = $this->dao->read(PaymentModeValue::class, $valueId);
        $this->assertNull($readValue, 'Value was not cascade deleted');
        $readReturn = $this->dao->read(PaymentModeReturn::class, $returnId);
        $this->assertNull($readReturn, 'Return was not cascade deleted');
        $readPm = $this->dao->read(PaymentMode::class, $pmId);
        $this->assertNull($readPm,
                'Payment mode was not deleted while cascading');
    }

    /** @depends testValues
     * @depends testOtherReturn */
    public function testDeleteCascadeReferenced() {
        // $pm return references $pm2
        // Try to delete $pm2: should fail.
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $pm2 = new PaymentMode();
        $pm2->setReference('back');
        $pm2->setLabel('Back');
        $pm2->setBackLabel('Mode back');
        $return = new PaymentModeReturn();
        $return->setMinAmount(1.0);
        $return->setReturnMode($pm2);
        $pm->addReturn($return);
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        $this->api->write(array($pm, $pm2));
        $foundException = false;
        try {
            $this->api->delete($pm2->getId());
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $foundException = true;
        } catch (\Doctrine\DBAL\Exception\DriverException $e) { // With Sqlite
            $foundException = true;
        }
        $this->assertTrue($foundException, 'Expected exception was not thrown.');
    }

    public function testDeleteCascadeReferencee() {
        // $pm return references $pm2
        // Try to delete $pm: should work
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $pm2 = new PaymentMode();
        $pm2->setReference('back');
        $pm2->setLabel('Back');
        $return = new PaymentModeReturn();
        $return->setMinAmount(1.0);
        $return->setReturnMode($pm2);
        $pm->addReturn($return);
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        // Write and get Ids
        $this->api->write(array($pm, $pm2));
        $pmId = $pm->getId();
        $pm2Id = $pm2->getId();
        $returnId = $return->getId();
        $valueId = $value->getId();
        // Delete
        $count = $this->api->delete($pm->getId());
        $this->assertEquals(1, $count);
        // Check content
        $readValue = $this->dao->read(PaymentModeValue::class, $valueId);
        $this->assertNull($readValue, 'Value was not cascade deleted');
        $readReturn = $this->dao->read(PaymentModeReturn::class, $returnId);
        $this->assertNull($readReturn, 'Return was not cascade deleted');
        $readPm = $this->dao->read(PaymentMode::class, $pmId);
        $this->assertNull($readPm,
                'Payment mode was not deleted while cascading');
        $readPm2 = $this->dao->read(PaymentMode::class, $pm2Id,
                'Referenced payment mode was deleted but it should not');
        $this->assertNotNull($readPm2);
        $this->assertEquals($pm2->getReference(), $readPm2->getReference());
        $this->assertEquals($pm2->getLabel(), $readPm2->getLabel());
    }

    /** @depends testSelfReturn
     * @depends testOtherReturn */
    public function testReplace() {
        // Write pm self referencing with return and one value and pm2
        // Update pm to switch return to pm2 and update value
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $pm2 = new PaymentMode();
        $pm2->setReference('back');
        $pm2->setLabel('Back');
        $return = new PaymentModeReturn();
        $return->setMinAmount(1.0);
        $return->setReturnMode($pm);
        $pm->addReturn($return);
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        $this->api->write(array($pm, $pm2));
        $pmId = $pm->getId();
        $pm2Id = $pm2->getId();
        $newPm = PaymentMode::load('mode', $this->dao);
        $newPm->merge(['label' => 'New label',
                'values' => [['value' => 1.0]],
                'returns' => [['minAmount' => 0.0, 'returnMode' => $pm2Id]]],
                $this->dao);
        $this->api->write($newPm);
        $count = $this->api->count();
        $this->assertEquals(2, $count, // There is pm2 so 2
                'Payment mode was inserted instead of updated');
        $read = $this->dao->readSnapshot(PaymentMode::class, $pmId);
        $this->assertEquals($newPm->getReference(), $read->getReference());
        $this->assertEquals($newPm->getLabel(), $read->getLabel());
        $this->assertEquals(1, $read->getValues()->count());
        $this->assertEquals(1.0, $read->getValues()->get(0)->getValue());
        $this->assertEquals(1, $read->getReturns()->count());
        $this->assertEquals($pm2Id, $read->getReturns()->get(0)->getReturnMode()->getId());
        $this->assertEquals(0.0, $read->getReturns()->get(0)->getMinAmount());
    }

    public function testPMVImageKeep() {
        // Write an image for a payment mode value
        // Update the payment mode but keep the value
        // The image must still be there and linked
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        $this->api->write($pm);
        $imgJsId = json_encode($value->getId());
        $img = new Image();
        $img->setImage(file_get_contents(__DIR__ . '/../res/image.png'));
        $img->setMimeType('image/png');
        $img->setModel(Image::MODEL_PAYMENTMODE_VALUE);
        $modelId = Image::getPMVModelId($value);
        $img->setModelId($modelId);
        $imgApi = new ImageAPI(null, $this->dao);
        $imgApi->write($img);
        // Update payment mode
        $value2 = new PaymentModeValue();
        $value2->setValue(5.0);
        $pm->addValue($value2);
        $this->api->write($pm);
        $readPm = $this->dao->readSnapshot(PaymentMode::class, $pm->getId());
        foreach ($readPm->getValues() as $v) {
            if ($v->getValue() == $value->getValue()) {
                $this->assertTrue($v->hasImage());
            } else {
                $this->assertFalse($v->hasImage());
            }
        }
        $readImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PAYMENTMODE_VALUE, 'modelId' => $modelId]);
        $this->assertNotNull($readImg);
    }

    public function testPMVImageOrphaned() {
        // Write an image for a payment mode value
        // Update the payment mode with a new value
        // The image must be deleted
        // Other payment modes must remain untouched
        $pm = new PaymentMode();
        $pm->setReference('mode');
        $pm->setLabel('Mode');
        $value = new PaymentModeValue();
        $value->setValue(10.0);
        $pm->addValue($value);
        $this->api->write($pm);
        $img = new Image();
        $img->setImage(file_get_contents(__DIR__ . '/../res/image.png'));
        $img->setMimeType('image/png');
        $img->setModel(Image::MODEL_PAYMENTMODE_VALUE);
        $modelId = Image::getPMVModelId($value);
        $img->setModelId($modelId);
        $imgApi = new ImageAPI(null, $this->dao);
        $imgApi->write($img);
        $pm2 = new PaymentMode();
        $pm2->setReference('mode2');
        $pm2->setLabel('Mode2');
        $valuePm2 = new PaymentModeValue();
        $valuePm2->setValue(10.0);
        $pm2->addValue($valuePm2);
        $this->api->write($pm2);
        $img2 = new Image();
        $img2->setImage(file_get_contents(__DIR__ . '/../res/image.png'));
        $img2->setMimeType('image/png');
        $img2->setModel(Image::MODEL_PAYMENTMODE_VALUE);
        $modelId2 = Image::getPMVModelId($valuePm2);
        $img2->setModelId($modelId2);
        $imgApi = new ImageAPI(null, $this->dao);
        $imgApi->write($img2);
        // Update payment mode
        $pm->clearValues();
        $value2 = new PaymentModeValue();
        $value2->setValue(5.0);
        $pm->addValue($value2);
        $this->api->write($pm);
        $readPm = $this->dao->readSnapshot(PaymentMode::class, $pm->getId());
        $this->assertEquals(1, count($readPm->getValues()));
        $this->assertFalse($readPm->getValues()->first()->hasImage());
        $readPm2 = $this->dao->readSnapshot(PaymentMode::class, $pm2->getId());
        $this->assertEquals(1, count($readPm2->getValues()));
        $this->assertTrue($readPm2->getValues()->first()->hasImage());
        $readImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PAYMENTMODE_VALUE, 'modelId' => $modelId]);
        $this->assertNull($readImg);
        $readImg2 = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PAYMENTMODE_VALUE, 'modelId' => $modelId2]);
        $this->assertNotNull($readImg2);
    }
}
