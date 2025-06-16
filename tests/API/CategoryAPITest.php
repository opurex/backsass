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

use \Pasteque\Server\API\CategoryAPI;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class CategoryAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new CategoryAPI($this->dao);
    }

    protected function tearDown(): void {
        // Explicitely delete children before parents because Doctrine
        // cannot handle it. It won't work if there grandchildren.
        $children = $this->dao->search(Category::class,
                new DAOCondition('parent', '!=', null));
        foreach ($children as $record) {
            $this->dao->delete($record);
        }
        $parents = $this->dao->search(Category::class,
                new DAOCondition('parent', '=', null));
        foreach ($parents as $record) {
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

    /** Read, write and delete a category without any reference. */
    public function testNoRef() {
        $cat = new Category();
        $cat->setReference('ref');
        $cat->setLabel('Test');
        $this->api->write($cat);
        $read = $this->api->get($cat);
        $this->checkEquality($cat, $read);
        $id = $cat->getId();
        $this->api->delete($cat);
        $read = $this->api->get($id);
        $this->assertNull($read);
    }

    /** @depends testNoRef */
    public function testWriteParent() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $this->api->write($parent);
        $this->api->write($child);
        $parentId = $parent->getId();
        $childId = $child->getId();
        $readChild = $this->api->get($childId);
        $this->assertEquals($parentId, $readChild->getParent()->getId());
    }

    /** @depends testWriteParent
     * Try to write the parent and children in one call. */
    public function testWriteParentArray() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $catData = array($parent, $child);
        $this->api->write($catData);
        $parentId = $parent->getId();
        $childId = $child->getId();
        $readChild = $this->api->get($childId);
        $this->assertEquals($parentId, $readChild->getParent()->getId());
    }

    /** @depends testWriteParent
     * Write a child without writing parent and except it to fail. */
    public function testWriteChildFirst() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $foundException = false;
        try {
            $this->api->write($child);
        } catch (\Doctrine\ORM\ORMInvalidArgumentException $e) {
            $foundException = true;
        }
        // Clean dao for the expected error not to propagate on teardown
        $this->dao->getEntityManager()->clear();
        $this->assertTrue($foundException, 'Expected exception was not thrown');
    }

    /** @depends testWriteParentArray
     * Write child and parent at the same time but child first
     * and except it to pass. */
    public function testWriteChildFirstArray() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $catData = array($child, $parent);
        $this->api->write($catData);
        $parentId = $parent->getId();
        $childId = $child->getId();
        $readChild = $this->api->get($childId);
        $this->assertEquals($parentId, $readChild->getParent()->getId());
    }

    /** @depends testWriteParent
     * Unlink parent and child, except both to be still there. */
    public function testUnsetParent() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $this->api->write($parent);
        $this->api->write($child);
        $parentId = $parent->getId();
        $childId = $child->getId();
        $child->setParent(null);
        $this->api->write($child);
        $readChild = $this->api->get($childId);
        $this->assertNull($readChild->getParent(), 'Parent was not set to null');
        $readParent = $this->api->get($parentId);
        $this->assertNotNull($readParent, 'Parent was deleted when children was unlinked');
    }

    /** @depends testWriteParent
     * Try to delete parent, expect it to fail. */
    public function testDeleteParent() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $catData = array($parent, $child);
        $this->api->write($catData);
        $parentId = $parent->getId();
        $childId = $child->getId();
        $foundException = false;
        try {
            $this->api->delete($parentId);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $foundException = true;
        } catch (\Doctrine\DBAL\Exception\DriverException $e) { // With Sqlite
            $foundException = true;
        }
        // Clean dao for the expected error not to propagate on teardown
        $this->dao->getEntityManager()->clear();
        $this->assertTrue($foundException, 'Expected exception was not thrown');
    }

    /* @depends testWriteParent */
    public function testDeleteRecursive() {
        $parent = new Category();
        $parent->setReference('parent');
        $parent->setLabel('Parent');
        $child = new Category();
        $child->setReference('child');
        $child->setLabel('Child');
        $child->setParent($parent);
        $catData = array($parent, $child);
        $this->api->write($catData);
        $parentId = $parent->getId();
        $childId = $child->getId();
        $this->assertEquals(2, $this->api->deleteRecursive($parentId));
        $this->assertNull($this->api->get($parentId), 'Parent was not deleted');
        $this->assertNull($this->api->get($childId), 'Child was not deleted');
    }
}
