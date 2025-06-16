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

use \Pasteque\Server\AppContext;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DBException;
use \Pasteque\Server\System\DAO\DoctrineDAO;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

/** Test for DoctrineDAO and also DoctrineModel, because they work together. */
class DoctrineDAOTest extends TestCase
{
    const MODEL_NAME = 'Pasteque\Server\Model\Category';
    private $dao;
    /** A category to test CRUD. */
    private $cat;

    protected function setUp(): void {
        $this->cat = new Category();
        $this->cat->setReference('ref');
        $this->cat->setLabel('label');
        $this->cat->setDispOrder(0);
    }

    protected function tearDown(): void {
        // Restore database in its empty state
        try {
            global $dbInfo;
            $dao = new DoctrineDAO($dbInfo);
            $ctx = $dao->getEntityManager()->getConnection();
            $ctx->executeUpdate('delete from categories where parent_id is not null');
            $ctx->executeUpdate('delete from categories');
            $dao->close();
        } catch (\Exception $e) { var_dump($e->getMessage()); /* ugly but it works... */ }
    }

    public function testCreateNoType() {
        $this->expectException(\Pasteque\Server\System\DAO\DBException::class);
        $this->expectExceptionCode(\Pasteque\Server\System\DAO\DBException::CODE_TYPE_ERROR);
        $dbInfo = ['host' => 'localhost', 'name' => 'test',
            'user' => 'test', 'password' => 'test'];
        $dao = new DoctrineDAO($dbInfo);
    }

    public function testCreateWrongType() {
        $this->expectException(\Pasteque\Server\System\DAO\DBException::class);
        $this->expectExceptionCode(\Pasteque\Server\System\DAO\DBException::CODE_TYPE_ERROR);
        $dbInfo = ['host' => 'localhost', 'name' => 'test',
            'user' => 'test', 'password' => 'test',
            'type' => 'wrongsql'];
        $dao = new DoctrineDAO($dbInfo);
    }

    public function testCreateWrongDB() {
        global $dbInfo;
        $altInfo = ['type' => $dbInfo['type'], 'host' => $dbInfo['host'],
            'port' => $dbInfo['port'], 'user' => $dbInfo['user'],
            'password' => $dbInfo['password']];
        $altInfo['name'] = 'imnothere_hehe';
        $dao = new DoctrineDAO($altInfo);        
        $this->markTestIncomplete('Todo');
    }

    /** @doesNotPerformAssertions */
    public function testCreate() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->close();
    }

    public function testReadEmpty() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $read = $dao->read(static::MODEL_NAME, 1);
        $this->assertNull($read);
        $dao->close();
    }

    /** @depends testCreate
     * Test Write single, Commit, Read */
    public function testWCR() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->write($this->cat);
        $dao->commit();
        $id = $this->cat->getId();
        $this->assertNotNull($this->cat->getId(),
            "Written data wasn't given an ID");
        $read = $dao->read(static::MODEL_NAME, $id);
        $dao->close();
        $this->assertNotNull($read, 'Inserted data not found');
        $catStruct = $this->cat->toStruct();
        $readStruct = $read->toStruct();
        $this->assertEquals($catStruct['id'], $readStruct['id']);
        $this->assertEquals($catStruct['reference'], $readStruct['reference']);
        $this->assertEquals($catStruct['label'], $readStruct['label']);
        $this->assertEquals($catStruct['hasImage'], $readStruct['hasImage']);
        $this->assertEquals($catStruct['dispOrder'], $readStruct['dispOrder']);
    }

    /** @depends testCreate
     * Test read/write with a link between records. Ensure that the linked
     * records are fully readable. */
    public function testReadAssoc() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->write($this->cat);
        $dao->commit();
        $subcat = new Category();
        $subcat->setReference('subcat');
        $subcat->setLabel('Subcat');
        $subcat->setParent($this->cat);
        $dao->write($subcat);
        $dao->commit();
        $readChild = $dao->read(static::MODEL_NAME, $subcat->getId());
        $dao->close();
        $readParent = $readChild->getParent();
        $this->assertEquals($this->cat->getId(), $readParent->getId());
    }

    /** @depends testCreate
     * @depends testWCR */
    public function testNoAutocommit() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->write($this->cat);
        $id = $this->cat->getId();
        $read = $dao->readSnapshot(static::MODEL_NAME, $id);
        $dao->close();
        $this->assertNull($read, 'Category was autocommited');
    }

    /** @depends testCreate
     * @depends testWCR
     * Test count (0), write/commit, count (1) */
    public function testCount() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $this->assertEquals(0, $dao->count(static::MODEL_NAME));
        $dao->write($this->cat);
        $dao->commit();
        $this->assertEquals(1, $dao->count(static::MODEL_NAME));
        $dao->close();
    }

    /** @depends testCreate
     * @doesNotPerformAssertions
     * Test Delete, Commit (nothing) .*/
    public function testDC() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->delete($this->cat);
        $dao->commit();
        $dao->close();
    }

    /** @depends testCreate
     * @depends testWCR
     * Test Write, Commit, Delete, Commit, Read */
    public function testWCDCR() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->write($this->cat);
        $dao->commit();
        $id = $this->cat->getId();
        $dao->delete($this->cat);
        $dao->commit();
        $read = $dao->read(static::MODEL_NAME, $id);
        $dao->close();
        $this->assertNull($read, "Data wasn't deleted");
    }

    /** @depends testCreate
     * @depends testWCR
     * Test Write, Commit, Delete, Read */
    public function testWCDR() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $dao->write($this->cat);
        $dao->commit();
        $id = $this->cat->getId();
        $dao->delete($this->cat);
        $read = $dao->read('\Pasteque\Server\Model\Category', $id);
        $dao->close();
        $this->assertNotNull($read, 'Delete was auto-commited');
    }

    private function buildCat($name) {
        $cat = new Category();
        $cat->setReference(strtoupper($name));
        $cat->setLabel($name);
        return $cat;
    }

    /** @depends testWCR */
    public function testReadSnapshot() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $a->setLabel('edited');
        $snap = $dao->readSnapshot(static::MODEL_NAME, $a->getId());
        $this->assertEquals('a', $snap->getLabel());
    }

    /** @depends testWCR */
    public function testSearchAll() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $b = $this->buildCat('b');
        $c = $this->buildCat('c');
        $a = $this->buildCat('a');
        $d = $this->buildCat('d');
        $dao->write($b); $dao->write($c); $dao->write($a); $dao->write($d);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME);
        $this->assertEquals(4, count($search));
    }

    /** @depends testWCR */
    public function testSearchEq() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $b = $this->buildCat('b');
        $c = $this->buildCat('c');
        $a = $this->buildCat('a');
        $d = $this->buildCat('d');
        $dao->write($b); $dao->write($c); $dao->write($a); $dao->write($d);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, new DAOCondition('reference', '=', 'B'));
        $this->assertEquals(1, count($search));
        $this->assertEquals('B', $search[0]->getReference());
    }

    /** @depends testSearchAll */
    public function testSearchOrderAsc() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $b = $this->buildCat('b');
        $c = $this->buildCat('c');
        $a = $this->buildCat('a');
        $d = $this->buildCat('d');
        $dao->write($b); $dao->write($c); $dao->write($a); $dao->write($d);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, null, null, null, 'label');
        $this->assertEquals(4, count($search));
        $this->assertEquals('a', $search[0]->getLabel());
        $this->assertEquals('b', $search[1]->getLabel());
        $this->assertEquals('c', $search[2]->getLabel());
        $this->assertEquals('d', $search[3]->getLabel());
    }

    /** @depends testSearchAll */
    public function testSearchOrderDesc() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $b = $this->buildCat('b');
        $c = $this->buildCat('c');
        $a = $this->buildCat('a');
        $d = $this->buildCat('d');
        $dao->write($b); $dao->write($c); $dao->write($a); $dao->write($d);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, null, null, null, '-label');
        $this->assertEquals(4, count($search));
        $this->assertEquals('d', $search[0]->getLabel());
        $this->assertEquals('c', $search[1]->getLabel());
        $this->assertEquals('b', $search[2]->getLabel());
        $this->assertEquals('a', $search[3]->getLabel());
    }

    /** Search for with associtive field criteria
     * @depends testSearchEq
     * @depends testSearchOrderAsc */
    public function testSearchEqAssoc() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $b = $this->buildCat('b');
        $c = $this->buildCat('c');
        $c->setParent($a);
        $d = $this->buildCat('d');
        $d->setParent($a);
        $dao->write($a); $dao->write($b); $dao->write($c); $dao->write($d);
        $dao->commit();
        $searchRef = $dao->search(static::MODEL_NAME, new DAOCondition('parent', '=', $a),
                null, null, 'reference');
        $this->assertEquals(2, count($searchRef));
        $this->assertEquals('C', $searchRef[0]->getReference());
        $this->assertEquals('D', $searchRef[1]->getReference());
    }

    /** @depends testSearchAll */
    public function testSearchPagination() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $b = $this->buildCat('b');
        $c = $this->buildCat('c');
        $a = $this->buildCat('a');
        $d = $this->buildCat('d');
        $dao->write($b); $dao->write($c); $dao->write($a); $dao->write($d);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, null, 2, 1, 'label');
        $this->assertEquals(2, count($search));
        $this->assertEquals('b', $search[0]->getLabel());
        $this->assertEquals('c', $search[1]->getLabel());
    }

    /** @depends testSearchEq */
    public function testSearchNoResult() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, new DAOCondition('reference', '=', 'B'));
        $this->assertEquals(0, count($search));
    }

    /** @depends testSearchPagination */
    public function testSearchPaginationNoResult() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, new DAOCondition('reference', '=', 'B'),
                1, 0);
        $this->assertEquals(0, count($search));
    }

    /** @depends testSearchEq */
    public function testSearchAnd() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $search = $dao->search(static::MODEL_NAME, [new DAOCondition('reference', '=', 'A'), new DAOCondition('label', '=', 'a')]);
        $this->assertEquals(1, count($search));
        $this->assertEquals($a->getId(), $search[0]->getId());
        $search = $dao->search(static::MODEL_NAME, [new DAOCondition('reference', '=', 'A'), new DAOCondition('label', '=', 'b')]);
        $this->assertEquals(0, count($search));
    }

    public function testLoadNoRecord() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $struct = ['reference' => 'ref', 'label' => 'lbl', 'parent' => null,
                'dispOrder' => 2];
        $cat = Category::load($struct['reference'], $dao);
        $this->assertNull($cat);
    }

    public function testMergeNew() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $struct = ['reference' => 'ref', 'label' => 'lbl', 'parent' => null,
                'dispOrder' => 2];
        $cat = new Category();
        $cat->merge($struct, $dao);
        $this->assertNull($cat->getId());
        $this->assertEquals('ref', $cat->getReference());
        $this->assertEquals('lbl', $cat->getLabel());
        $this->assertEquals(2, $cat->getDispOrder());
        $this->assertNull($cat->getParent());
    }

    public function testMergeEdit() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $struct = ['reference' => 'ref', 'label' => 'lbl', 'parent' => null,
                'dispOrder' => 2];
        $cat = new Category();
        $cat->setLabel('oldLbl');
        $cat->setReference('oldRef');
        $cat->setDispOrder(1);
        $cat->merge($struct, $dao);
        $this->assertEquals('ref', $cat->getReference());
        $this->assertEquals('lbl', $cat->getLabel());
        $this->assertEquals(2, $cat->getDispOrder());
        $this->assertNull($cat->getParent());
    }

    /** @depends testMergeEdit */
    public function testMergeKeep() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $struct = ['label' => 'lbl', 'parent' => null, 'dispOrder' => 2];
        $cat = new Category();
        $cat->setLabel('oldLbl');
        $cat->setReference('oldRef');
        $cat->setDispOrder(1);
        $cat->merge($struct, $dao);
        $this->assertEquals('oldRef', $cat->getReference());
        $this->assertEquals('lbl', $cat->getLabel());
        $this->assertEquals(2, $cat->getDispOrder());
        $this->assertNull($cat->getParent());
    }

    /** @depends testCreate */
    public function testLoadFromId() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $cat = Category::loadFromId($a->getId(), $dao);
        $this->assertNotNull($cat);
        $this->assertEquals($a->getId(), $cat->getId());
        $this->assertEquals($a->getReference(), $cat->getReference());
        $this->assertEquals($a->getLabel(), $cat->getLabel());
        $this->assertEquals($a->getParent(), $cat->getParent());
        $this->assertEquals($a->getDispOrder(), $cat->getDispOrder());
    }

    /** @depends testCreate */
    public function testLoadFromWrongId() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $cat = Category::loadFromId($a->getId() + 1, $dao);
        $this->assertNull($cat);
    }

    /** @depends testCreate */
    public function testLoad() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $cat = Category::load($a->getReference(), $dao);
        $this->assertNotNull($cat);
        $this->assertEquals($a->getId(), $cat->getId());
        $this->assertEquals($a->getReference(), $cat->getReference());
        $this->assertEquals($a->getLabel(), $cat->getLabel());
        $this->assertEquals($a->getParent(), $cat->getParent());
        $this->assertEquals($a->getDispOrder(), $cat->getDispOrder());
    }

    public function testLoadWrongRef() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $cat = Category::load('not a', $dao);
        $this->assertNull($cat);
    }

    /** @depends testWCR */
    public function testMergeWrongChildId() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $a = $this->buildCat('a');
        $dao->write($a);
        $dao->commit();
        $struct = ['id' => $a->getId(), 'reference' => 'editedRef',
                'label' => 'editedLabel', 'parent' => $a->getId() + 2,
                'dispOrder' => 2];
        $exceptionThrown = false;
        try {
            $cat = Category::load($a->getReference(), $dao);
            $cat->merge($struct, $dao);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(
                    InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                    $e->getConstraint());
            $this->assertEquals(static::MODEL_NAME, $e->getClass());
            $this->assertEquals('parent', $e->getField());
            $this->assertEquals($a->getReference(), $e->getId()['reference']);
            $this->assertEquals($a->getId() + 2, $e->getValue());
        }
        $this->assertTrue($exceptionThrown);
    }

    /** Check if the model created is linked to Doctrine
     * @depends testMergeEdit */
    public function testSharedReference() {
        global $dbInfo;
        $dao = new DoctrineDAO($dbInfo);
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $dao->write($cat);
        $dao->commit();
        $id = $cat->getId();
        $struct = array('id' => $id, 'reference' => 'Edit', 'label' => 'edited');
        $editCat = Category::loadFromId($struct['id'], $dao);
        $editCat->merge($struct, $dao);
        $dao->write($editCat);
        $dao->commit();
        $this->assertEquals($cat->getId(), $editCat->getId());
        $read = $dao->readSnapshot(Category::class, $id);
        $this->assertEquals('Edit', $read->getReference());
        $this->assertEquals('edited', $read->getLabel());
    }
}
