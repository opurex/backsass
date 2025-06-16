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

use \Pasteque\Server\API\APIHelper;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class APIHelperMock extends APIHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\Category';
}

class APIHelperTest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new APIHelperMock($this->dao);
    }

    protected function tearDown(): void {
        $all = $this->dao->search(APIHelperMock::MODEL_NAME, null);
        foreach ($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    private function checkEquality($refCat, $testCat) {
        $this->assertEquals($refCat->getId(), $testCat->getId());
        $this->assertEquals($refCat->getReference(), $testCat->getReference());
        $this->assertEquals($refCat->getLabel(), $testCat->getLabel());
    }

    public function testGetEmpty() {
        $get = $this->api->get(1);
        $this->assertNull($get);
    }
    public function testGet() {
        $cat = new Category();
        $cat->setReference('ref');
        $cat->setLabel('Test');
        $this->dao->write($cat);
        $this->dao->commit();
        $get = $this->api->get($cat->getId());
        $this->checkEquality($cat, $get);
    }

    public function testGetAllEmpty() {
        $getAll = $this->api->getAll();
        $this->assertEquals(0, count($getAll));
    }

    public function testGetAll() {
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $cat2 = new Category();
        $cat2->setReference('ref2');
        $cat2->setLabel('Test2');
        $this->dao->write($cat);
        $this->dao->write($cat2);
        $this->dao->commit();
        $getAll = $this->api->getAll();
        $this->assertEquals(2, count($getAll));
        // getAll doesn't guaranty any ordering
        if ($getAll[0]->getReference() == 'ref1') {
            $this->checkEquality($cat, $getAll[0]);
            $this->checkEquality($cat2, $getAll[1]);
        } else {
            $this->checkEquality($cat, $getAll[1]);
            $this->checkEquality($cat2, $getAll[0]);
        }
    }

    public function testSearch() {
        // This test rely upon DAO->search test so it won't be exhaustive.
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $cat2 = new Category();
        $cat2->setReference('ref2');
        $cat2->setLabel('Test2');
        $this->dao->write($cat);
        $this->dao->write($cat2);
        $this->dao->commit();
        $search = $this->api->search(new DAOCondition('reference', '=', 'ref2'));
        $this->assertEquals(1, count($search));
        $this->checkEquality($cat2, $search[0]);
    }

    // Write relies upon DAO->write so the update/create
    // and other test won't be exhaustive.
    public function testWrite() {
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $resCat = $this->api->write($cat);
        $this->assertNotNull($resCat);
        $this->assertEquals($cat->getId(), $resCat->getId());
        $read = $this->dao->read(APIHelperMock::MODEL_NAME, $resCat->getId());
        $this->checkEquality($cat, $read);
    }

    public function testWriteArray() {
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $cat2 = new Category();
        $cat2->setReference('ref2');
        $cat2->setLabel('Test2');
        $writeData = array($cat, $cat2);
        $resCats = $this->api->write($writeData);
        $this->assertNotNull($resCats[0]->getId());
        $this->assertNotNull($resCats[1]->getId());
        $this->assertEquals(2, count($resCats));
        $this->assertEquals($cat->getId(), $resCats[0]->getId());
        $this->assertEquals($cat2->getId(), $resCats[1]->getId());
        $read = $this->dao->read(APIHelperMock::MODEL_NAME, $resCats[0]->getId());
        $read2 = $this->dao->read(APIHelperMock::MODEL_NAME, $resCats[1]->getId());
        $this->checkEquality($cat, $read);
        $this->checkEquality($cat2, $read2);
    }

    public function testWriteInvalid() {
        $this->expectException(\InvalidArgumentException::class);
        $this->api->write($this->dao);
    }

    public function testWriteArrayInvalid() {
        $this->expectException(\InvalidArgumentException::class);
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $writeData = array($cat, $this->dao);
        $this->api->write($writeData);
    }

    public function testDeleteEmpty() {
        $count = $this->api->delete(1);
        $this->assertEquals(0, $count);
    }

    public function testDelete() {
        $cat = new Category();
        $cat->setReference('ref');
        $cat->setLabel('Test');
        $this->dao->write($cat);
        $this->dao->commit();
        $id = $cat->getId();
        $count = $this->api->delete($id);
        $this->assertEquals(1, $count);
        $read = $this->dao->read(APIHelperMock::MODEL_NAME, $id);
        $this->assertNull($read);
    }

    public function testDeleteArray() {
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $cat2 = new Category();
        $cat2->setReference('ref2');
        $cat2->setLabel('Test2');
        $this->dao->write($cat);
        $this->dao->write($cat2);
        $this->dao->commit();
        $ids = array($cat->getId(), $cat2->getId());
        $count = $this->api->delete($ids);
        $this->assertEquals(2, $count);
        $read = $this->dao->read(APIHelperMock::MODEL_NAME, $ids[0]);
        $read2 = $this->dao->read(APIHelperMock::MODEL_NAME, $ids[1]);
        $this->assertNull($read);
        $this->assertNull($read2);
    }

    public function testDeletePartialArray() {
        $cat = new Category();
        $cat->setReference('ref1');
        $cat->setLabel('Test1');
        $cat2 = new Category();
        $cat2->setReference('ref2');
        $cat2->setLabel('Test2');
        $this->dao->write($cat);
        $this->dao->write($cat2);
        $this->dao->commit();
        $ids = array($cat->getId(), 1337);
        $count = $this->api->delete($ids);
        $this->assertEquals(1, $count);
        $read = $this->dao->read(APIHelperMock::MODEL_NAME, $ids[0]);
        $this->assertNull($read);
    }
}
