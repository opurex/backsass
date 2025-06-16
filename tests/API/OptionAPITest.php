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

use \Pasteque\Server\CommonAPI\OptionAPI;
use \Pasteque\Server\Exception\InvalidRecordException;
use \Pasteque\Server\Model\Option;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class OptionAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new OptionAPI($this->dao);
    }

    protected function tearDown(): void {
        $all = $this->dao->search(Option::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetAll() {
        $opt1 = new Option();
        $opt1->setName('opt1');
        $opt1->setContent('val1');
        $this->dao->write($opt1);
        $opt2 = new Option();
        $opt2->setName('opt2');
        $opt2->setContent('val2');
        $this->dao->write($opt2);
        $optSys = new Option();
        $optSys->setName('system');
        $optSys->setContent('sys');
        $optSys->setSystem(true);
        $this->dao->write($optSys);
        $this->dao->commit();
        $opts = $this->api->getAll();
        $this->assertEquals(2, count($opts));
        $this->assertEquals('opt1', $opts[0]->getName());
        $this->assertEquals('val1', $opts[0]->getContent());
        $this->assertFalse($opts[0]->isSystem());
        $this->assertEquals('opt2', $opts[1]->getName());
        $this->assertEquals('val2', $opts[1]->getContent());
        $this->assertFalse($opts[1]->isSystem());
    }

    public function testWrite() {
        $opt1 = new Option();
        $opt1->setName('opt1');
        $opt1->setContent('val1');
        $this->api->write($opt1);
        $snap = $this->dao->readSnapshot(Option::class, $opt1->getName());
        $this->assertNotNull($snap);
        $this->assertEquals($opt1->getName(), $snap->getName());
        $this->assertEquals($opt1->getContent(), $snap->getContent());
        $this->assertEquals($opt1->isSystem(), $snap->isSystem());
    }

    public function testWriteWithSys() {
        $opt1 = new Option();
        $opt1->setName('opt1');
        $opt1->setContent('val1');
        $opt1->setSystem(true);
        $exceptionThrown = false;
        try {
            $this->api->write($opt1);
        } catch (InvalidRecordException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidRecordException::CSTR_READ_ONLY,
                    $e->getConstraint());
            $this->assertEquals(Option::class, $e->getClass());
            $this->assertEquals($opt1->getName(), $e->getId());
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testWriteSys() {
        $opt1 = new Option();
        $opt1->setName('opt1');
        $opt1->setContent('val1');
        $opt1->setSystem(true);
        $this->api->writeSystem($opt1);
        $snap = $this->dao->readSnapshot(Option::class, $opt1->getName());
        $this->assertNotNull($snap);
        $this->assertEquals($opt1->getName(), $snap->getName());
        $this->assertEquals($opt1->getContent(), $snap->getContent());
        $this->assertEquals($opt1->isSystem(), $snap->isSystem());
    }

    public function testDelete() {
        $opt1 = new Option();
        $opt1->setName('opt1');
        $opt1->setContent('val1');
        $this->dao->write($opt1);
        $this->dao->commit();
        $this->api->delete('opt1');
        $this->assertEquals(0, $this->dao->count(Option::class));
    }

    public function testDeleteSys() {
        $opt1 = new Option();
        $opt1->setName('opt1');
        $opt1->setContent('val1');
        $opt1->setSystem(true);
        $this->dao->write($opt1);
        $this->dao->commit();
        $exceptionThrown = false;
        try {
            $this->api->delete('opt1');
        } catch (InvalidRecordException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidRecordException::CSTR_READ_ONLY,
                    $e->getConstraint());
            $this->assertEquals(Option::class, $e->getClass());
            $this->assertEquals($opt1->getName(), $e->getId());
        }
        $this->assertTrue($exceptionThrown);
    }
}
