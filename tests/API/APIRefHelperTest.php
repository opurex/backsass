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

use \Pasteque\Server\API\APIRefHelper;
use \Pasteque\Server\Exception\UnicityException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class APIRefHelperMock extends APIRefHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\Category';
}

class APIRefHelperTest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new APIRefHelperMock($this->dao);
    }

    protected function tearDown(): void {
        $all = $this->api->getAll();
        $ids = array();
        foreach($all as $record) {
            $ids[] = $record->getId();
        }
        $this->api->delete($ids);
        $this->dao->close();
    }

    public function testForceWrite() {
        $cat = new Category();
        $cat->setReference('A');
        $cat->setLabel('a');
        $this->dao->write($cat);
        $this->dao->commit();
        $id = $cat->getId();
        $cat2 = new Category();
        $cat2->setReference('A');
        $cat2->setLabel('edited');
        $resCat = $this->api->forceWrite($cat2);
        $this->assertEquals(1, $this->api->count());
        $this->assertEquals($cat->getId(), $resCat->getId());
        $read = $this->dao->readSnapshot(Category::class, $resCat->getId());
        $this->assertEquals($cat2->getLabel(), $read->getLabel());
    }

    public function testUnicityErrorInData() {
        $cat = new Category();
        $cat->setReference('A');
        $cat->setLabel('u');
        $cat2 = new Category();
        $cat2->setReference('A');
        $cat2->setLabel('w');
        $exceptionThrown = false;
        try {
            $this->api->write([$cat, $cat2]);
        } catch (UnicityException $e) {
            $exceptionThrown = true;
            $this->assertEquals('reference', $e->getField());
            $this->assertEquals('A', $e->getValue());
        }
        $this->assertTrue($exceptionThrown, 'Expecting UnicityException.');
    }

    public function testUnicityErrorPreviousData() {
        $cat = new Category();
        $cat->setReference('A');
        $cat->setLabel('u');
        $this->api->write($cat);
        $cat2 = new Category();
        $cat2->setReference('A');
        $cat2->setLabel('w');
        $exceptionThrown = false;
        try {
            $this->api->write($cat2);
        } catch (UnicityException $e) {
            $exceptionThrown = true;
            $this->assertEquals('reference', $e->getField());
            $this->assertEquals('A', $e->getValue());
        }
        $this->assertTrue($exceptionThrown, 'Expecting UnicityException.');
    }

    public function testGetByReference() {
        $cat = new Category();
        $cat->setReference('A');
        $cat->setLabel('a');
        $this->dao->write($cat);
        $this->dao->commit();
        $read = $this->api->getByReference($cat->getReference());
        $this->assertNotNull($read);
        $this->assertEquals($cat->getId(), $read->getId());
    }

    public function testGetByRefEmpty() {
        $cat = new Category();
        $cat->setReference('A');
        $cat->setLabel('a');
        $this->dao->write($cat);
        $this->dao->commit();
        $read = $this->api->getByReference('B');
        $this->assertNull($read);
    }
}
