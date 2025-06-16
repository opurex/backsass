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

use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

/** Test DoctrineModel functions through Category */
class ResourceTest extends TestCase
{
    private $dao;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
    }

    protected function tearDown(): void {
        $res = $this->dao->search(Resource::class);
        foreach ($res as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetContentStraight() {
        $res = new Resource();
        $res->setLabel('Test');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $this->assertEquals('Text content', $res->getContent());
        $resBin = new Resource();
        $resBin->setLabel('Bin test');
        $resBin->setType(Resource::TYPE_BIN);
        $resBin->setContent(0x2a);
        $this->assertEquals(0x2a, $resBin->getContent());
    }

    public function testGetContentDB() {
        $res = new Resource();
        $res->setLabel('Test');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Text content');
        $this->dao->write($res);
        $this->dao->commit();
        $this->assertEquals('Text content', $res->getContent());
        $resSnap = $this->dao->readSnapshot(Resource::class, 'Test');
        $this->assertEquals('Text content', $resSnap->getContent());
        $resBin = new Resource();
        $resBin->setLabel('Bin test');
        $resBin->setType(Resource::TYPE_BIN);
        $resBin->setContent(0x2a);
        $this->dao->write($resBin);
        $this->dao->commit();
        $this->assertEquals(0x2a, $resBin->getContent());
        $resBinSnap = $this->dao->readSnapshot(Resource::class, 'Bin test');
        $this->assertEquals(0x2a, $resBinSnap->getContent());
    }

    public function testToStructText() {
        $res = new Resource();
        $res->setLabel('Test');
        $res->setType(Resource::TYPE_TEXT);
        $res->setContent('Content');
        $struct = $res->toStruct();
        $this->assertEquals('Test', $struct['label']);
        $this->assertEquals(Resource::TYPE_TEXT, $struct['type']);
        $this->assertEquals('Content', $struct['content']);
    }

    public function testToStructBin() {
        $res = new Resource();
        $res->setLabel('Test');
        $res->setType(Resource::TYPE_BIN);
        $res->setContent(0x2a);
        $struct = $res->toStruct();
        $this->assertEquals('Test', $struct['label']);
        $this->assertEquals(Resource::TYPE_BIN, $struct['type']);
        $this->assertEquals(0x2a, base64_decode($struct['content']));
    }

    public function testMergeText() {
        $struct = ['label' => 'Test', 'type' => Resource::TYPE_TEXT,
                'content' => 'Content'];
        $res = new Resource();
        $res->merge($struct, $this->dao);
        $this->assertEquals('Test', $res->getLabel());
        $this->assertEquals(Resource::TYPE_TEXT, $res->getType());
        $this->assertEquals('Content', $res->getContent());
    }

    public function testMergeBin() {
        $struct = ['label' => 'Test', 'type' => Resource::TYPE_BIN,
                'content' => base64_encode(0x2a)];
        $res = new Resource();
        $res->merge($struct, $this->dao);
        $this->assertEquals('Test', $res->getLabel());
        $this->assertEquals(Resource::TYPE_BIN, $res->getType());
        $this->assertEquals(0x2a, $res->getContent());
    }
}
