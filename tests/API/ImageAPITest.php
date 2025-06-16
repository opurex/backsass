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
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\Model\Image;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeValue;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class ImageAPITest extends TestCase
{
    const RES_DIR = __DIR__ . '/../../res/images/';
    private $dao;
    private $api;
    private $tax;
    private $prd;
    private $cat;
    private $role;
    private $user;
    private $customer;
    private $paymentMode;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new ImageAPI(null, $this->dao);
        $this->cat = new Category();
        $this->cat->setReference('cat');
        $this->cat->setLabel('category');
        $this->dao->write($this->cat);
        $this->tax = new Tax();
        $this->tax->setRate(0.1);
        $this->tax->setLabel('tax');
        $this->dao->write($this->tax);
        $this->prd = new Product();
        $this->prd->setReference('prd');
        $this->prd->setLabel('product');
        $this->prd->setCategory($this->cat);
        $this->prd->setPriceSell(10);
        $this->prd->setTax($this->tax);
        $this->dao->write($this->prd);
        $this->role = new Role();
        $this->role->setName('role');
        $this->dao->write($this->role);
        $this->user = new User();
        $this->user->setName('user');
        $this->user->setRole($this->role);
        $this->dao->write($this->user);
        $this->customer = new Customer();
        $this->customer->setDispName('customer');
        $this->dao->write($this->customer);
        $this->paymentMode = new PaymentMode();
        $this->paymentMode->setReference('pm');
        $this->paymentMode->setLabel('payment mode');
        $value = new PaymentModeValue();
        $value->setValue(1);
        $this->paymentMode->addValue($value);
        $this->dao->write($this->paymentMode);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $all = $this->dao->search(Image::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->tax);
        $this->dao->delete($this->cat);
        $this->dao->delete($this->prd);
        $this->dao->delete($this->user);
        $this->dao->delete($this->role);
        $this->dao->delete($this->customer);
        $values = $this->dao->search(PaymentModeValue::class);
        foreach ($values as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->paymentMode);
        $this->dao->commit();
        $this->dao->close();
    }

    public function testDefaultHasImageFalse() {
        $this->assertFalse($this->prd->hasImage());
        $this->assertFalse($this->cat->hasImage());
        $this->assertFalse($this->user->hasImage());
        $this->assertFalse($this->paymentMode->hasImage());
        $this->assertFalse($this->paymentMode->getValues()->get(0)->hasImage());
    }

    private function defaultTest($model, $fileName, $mimeType) {
        $img = $this->api->getDefault($model);
        $res = file_get_contents(static::RES_DIR . '/' . $fileName);
        $this->assertNotNull($img);
        $this->assertEquals($model, $img->getModel());
        $this->assertNull($img->getModelId());
        $this->assertEquals($mimeType, $img->getMimeType());
        $this->assertEquals($res, $img->getImage(), 'Image data mismatch');
    }

    public function testDefaultCategoryImage() {
        $this->defaultTest(Image::MODEL_CATEGORY, 'default_category.png', 'image/png');
    }

    public function testDefaultProductImage() {
        $this->defaultTest(Image::MODEL_PRODUCT, 'default_product.png', 'image/png');
    }
    public function testDefaultUserImage() {
        $this->defaultTest(Image::MODEL_USER, 'default_avatar.png', 'image/png');
    }
    public function testDefaultPaymentModeImage() {
        $this->defaultTest(image::MODEL_PAYMENTMODE, 'default_generic.png', 'image/png');
    }
    public function testDefaultPaymentModeValueImage() {
        $this->defaultTest(Image::MODEL_PAYMENTMODE_VALUE, 'default_generic.png', 'image/png');
    }

    public function testDefaultUnknownModel() {
        $exceptionThrown = false;
        try {
            $this->api->getDefault('cow');
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals('Enum', $e->getConstraint());
            $this->assertEquals('model', $e->getField());
            $this->assertEquals('cow', $e->getValue());
            $this->assertEquals('cow', $e->getId()['model']);
            $this->assertEquals('default', $e->getId()['modelId']);
        }
        $this->assertTrue($exceptionThrown);
    }

    private function sampleImage($model, $id) {
        $img = new Image();
        $img->setImage(file_get_contents(__DIR__ . '/../res/image.png'));
        $img->setMimeType('image/png');
        $img->setModel($model);
        $img->setModelId($id);
        return $img;
    }

    /** @depends testDefaultHasImageFalse */
    public function testSetImage() {
        // Set category image
        $img = $this->sampleImage(Image::MODEL_CATEGORY, $this->cat->getId());
        $this->api->write($img);
        $snap = $this->dao->readSnapshot(Category::class, $this->cat->getId());
        $this->assertTrue($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_CATEGORY,
                        'modelId' => $this->cat->getId()]);
        $this->assertNotNull($snapImg);
        // Set product image
        $img = $this->sampleImage(Image::MODEL_PRODUCT, $this->prd->getId());
        $this->api->write($img);
        $snap = $this->dao->readSnapshot(Product::class, $this->prd->getId());
        $this->assertTrue($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PRODUCT,
                        'modelId' => $this->prd->getId()]);
        $this->assertNotNull($snapImg);
        // Set user image
        $img = $this->sampleImage(Image::MODEL_USER, $this->user->getId());
        $this->api->write($img);
        $snap = $this->dao->readSnapshot(User::class, $this->user->getId());
        $this->assertTrue($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_USER,
                        'modelId' => $this->user->getId()]);
        $this->assertNotNull($snapImg);
        // Set payment mode image
        $img = $this->sampleImage(Image::MODEL_PAYMENTMODE,
                $this->paymentMode->getId());
        $this->api->write($img);
        $snap = $this->dao->readSnapshot(PaymentMode::class,
                $this->paymentMode->getId());
        $this->assertTrue($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PAYMENTMODE,
                        'modelId' => $this->paymentMode->getId()]);
        $this->assertNotNull($snapImg);
        // Set payment mode value image
        $pmvImgId = Image::getPMVModelId($this->paymentMode->getValues()->first());
        $img = $this->sampleImage(Image::MODEL_PAYMENTMODE_VALUE, $pmvImgId);
        $this->api->write($img);
        $snap = $this->dao->readSnapshot(PaymentModeValue::class,
                $this->paymentMode->getValues()->first()->getId());
        $this->assertTrue($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PAYMENTMODE_VALUE,
                        'modelId' => $pmvImgId]);
        $this->assertNotNull($snapImg);
    }

    public function testSetImageWrongModel() {
        $img = $this->sampleImage('cow', $this->cat->getId());
        $exceptionThrown = false;
        try {
            $this->api->write($img);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals('Enum', $e->getConstraint());
            $this->assertEquals('model', $e->getField());
            $this->assertEquals('cow', $e->getValue());
            $this->assertEquals('cow', $e->getId()['model']);
            $this->assertEquals($this->cat->getId(), $e->getId()['modelId']);
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testSetImageInexistingModel() {
        $img = $this->sampleImage(Image::MODEL_CATEGORY, 0);
        $exceptionThrown = false;
        try {
            $this->api->write($img);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(
                    InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                    $e->getConstraint());
            $this->assertEquals(Image::class, $e->getClass());
            $this->assertEquals('modelId', $e->getField());
            $this->assertEquals(Image::MODEL_CATEGORY, $e->getId()['model']);
            $this->assertEquals(0, $e->getId()['modelId']);
            $this->assertEquals(0, $e->getValue());
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testSetNotAnImage() {
        // Test thumbnailer failure
        $this->markTestIncomplete();
    }

    /** @depends testSetImage */
    public function testDeleteImage() {
        // Set and delete category image
        $img = $this->sampleImage(Image::MODEL_CATEGORY, $this->cat->getId());
        $this->api->write($img);
        $this->api->delete($img->getId());
        $snap = $this->dao->readSnapshot(Category::class, $this->cat->getId());
        $this->assertFalse($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class, ['model' => Image::MODEL_CATEGORY,
                        'modelId' => $this->cat->getId()]);
        $this->assertNull($snapImg);
        // Set and delete product image
        $img = $this->sampleImage(Image::MODEL_PRODUCT, $this->prd->getId());
        $this->api->write($img);
        $this->api->delete($img->getId());
        $snap = $this->dao->readSnapshot(Product::class, $this->prd->getId());
        $this->assertFalse($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class, ['model' => Image::MODEL_PRODUCT,
                        'modelId' => $this->prd->getId()]);
        $this->assertNull($snapImg);
        // Set and delete user image
        $img = $this->sampleImage(Image::MODEL_USER, $this->user->getId());
        $this->api->write($img);
        $this->api->delete($img->getId());
        $snap = $this->dao->readSnapshot(User::class, $this->user->getId());
        $this->assertFalse($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class, ['model' => Image::MODEL_USER,
                        'modelId' => $this->user->getId()]);
        $this->assertNull($snapImg);
        // Set and delete payment mode image
        $img = $this->sampleImage(Image::MODEL_PAYMENTMODE,
                $this->paymentMode->getId());
        $this->api->write($img);
        $this->api->delete($img->getId());
        $snap = $this->dao->readSnapshot(PaymentMode::class,
                $this->paymentMode->getId());
        $this->assertFalse($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class, ['model' => Image::MODEL_PAYMENTMODE,
                        'modelId' => $this->paymentMode->getId()]);
        $this->assertNull($snapImg);
        // Set and delete payment mode value image
        $pmvImgId = Image::getPMVModelId($this->paymentMode->getValues()->get(0));
        $img = $this->sampleImage(Image::MODEL_PAYMENTMODE_VALUE, $pmvImgId);
        $this->api->write($img);
        $this->api->delete($img->getId());
        $snap = $this->dao->readSnapshot(PaymentModeValue::class,
                $this->paymentMode->getValues()->first()->getId());
        $this->assertFalse($snap->hasImage());
        $snapImg = $this->dao->readSnapshot(Image::class,
                ['model' => Image::MODEL_PAYMENTMODE_VALUE,
                        'modelId' => $pmvImgId]);
        $this->assertNull($snapImg);
    }

    public function testDeleteImageWrongModel() {
        $img = $this->sampleImage('cow', $this->cat->getId());
        $exceptionThrown = false;
        try {
            $this->api->delete($img->getId());
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals('Enum', $e->getConstraint());
            $this->assertEquals('model', $e->getField());
            $this->assertEquals('cow', $e->getValue());
            $this->assertEquals('cow', $e->getId()['model']);
            $this->assertEquals($this->cat->getId(), $e->getId()['modelId']);
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testDeleteImageInexistingModel() {
        $img = $this->sampleImage(Image::MODEL_CATEGORY, -1);
        $deletedCount = $this->api->delete($img->getId());
        $this->assertEquals(0, $deletedCount);
    }
}
