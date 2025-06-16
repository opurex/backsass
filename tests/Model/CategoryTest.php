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

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

/** Test DoctrineModel functions through Category */
class CategoryTest extends TestCase
{
    private $dao;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
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

    public function testToStructNoRef() {
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $struct = $cat->toStruct();
        $this->assertNull($cat->getId());
        $this->assertNull($struct['id']);
        $this->assertEquals($cat->getReference(), $struct['reference']);
        $this->assertEquals($cat->getLabel(), $struct['label']);
        $this->assertNull($cat->getParent());
        $this->assertNull($struct['parent']);
        $this->assertEquals($cat->hasImage(), $struct['hasImage']);
        $this->assertEquals($cat->getDispOrder(), $struct['dispOrder']);
    }

    /** @depends testToStructNoRef */
    public function testToStructRef() {
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $childCat = new Category();
        $childCat->setReference('Child');
        $childCat->setLabel('child label');
        $childCat->setParent($cat);
        $this->dao->write($cat);
        $this->dao->write($childCat);
        $this->dao->commit();
        $struct = $childCat->toStruct();
        $this->assertEquals($cat->getId(), $struct['parent']);
    }

    public function testLoadMergeNoRef() {
        $default = new Category();
        $struct = array('reference' => 'Ref', 'label' => 'lbl');
        $cat = Category::load($struct['reference'], $this->dao);
        $this->assertNull($cat);
        $cat = new Category();
        $cat->merge($struct, $this->dao);
        $this->assertNull($cat->getId());
        $this->assertEquals($struct['reference'], $cat->getReference());
        $this->assertEquals($struct['label'], $cat->getLabel());
        $this->assertNull($cat->getParent());
        $this->assertFalse($cat->hasImage());
        $this->assertEquals($default->getDispOrder(), $cat->getDispOrder());
    }

    public function testMergeAutoset() {
        $cat = new Category();
        $cat->merge(['label' => 'cat'], $this->dao);
        $this->assertEquals('cat', $cat->getReference());
    }

    public function testMergeMissingValue() {
        $cat = new Category();
        $struct = ['dispOrder' => 1, 'reference' => 'cat'];
        $exceptionThrown = false;
        try {
            $cat->merge($struct, $this->dao);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidFieldException::CSTR_NOT_NULL,
                    $e->getConstraint());
            $this->assertEquals('label', $e->getField());
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testMergeWrongType() {
        $cat = new Category();
        $struct = ['reference' => 'Ref', 'label' => 'lbl',
                'dispOrder' => 'NaN'];
        $exceptionThrown = false;
        try {
            $cat->merge($struct, $this->dao);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidFieldException::CSTR_INT,
                    $e->getConstraint());
            $this->assertEquals(Category::class, $e->getClass());
            $this->assertEquals('dispOrder', $e->getField());
            $this->assertEquals('NaN', $e->getValue());
        }
        $this->assertTrue($exceptionThrown, 'Exception not thrown');
    }

    public function testMergeNull() {
        $cat = new Category();
        $struct = ['reference' => 'Ref', 'label' => 'lbl',
                'dispOrder' => null];
        $exceptionThrown = false;
        try {
            $cat->merge($struct, $this->dao);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidFieldException::CSTR_NOT_NULL,
                    $e->getConstraint());
            $this->assertEquals(Category::class, $e->getClass());
            $this->assertEquals('dispOrder', $e->getField());
            $this->assertEquals(null, $e->getValue());
        }
        $this->assertTrue($exceptionThrown, 'Exception not thrown');
    }

    public function testMergeParent() {
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $this->dao->write($cat);
        $this->dao->commit();
        $id = $cat->getId();
        $struct = array('reference' => 'Child', 'label' => 'child', 'parent' => $id);
        $child = new Category();
        $child->merge($struct, $this->dao);
        $parent = $child->getParent();
        $this->assertNotNull($parent);
        $this->assertEquals($id, $parent->getId());
        $this->assertEquals($cat->getReference(), $parent->getReference());
        $this->assertEquals($cat->getLabel(), $parent->getLabel());
        $this->assertEquals($cat->getDispOrder(), $parent->getDispOrder());
    }

    public function testGetLoadKey() {
        $valid = ['reference' => 'ref'];
        $validLoad = Category::getLoadKey($valid);
        $this->assertTrue(array_key_exists('reference', $validLoad));
        $this->assertEquals('ref', $validLoad['reference']);
        $invalid = ['notRef' => 'ref'];
        $invalidLoad = Category::getLoadKey($invalid);
        $this->assertNull($invalidLoad);
    }

    public function testEqualsSimple() {
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $cat2 = new Category();
        $cat2->setReference('Ref');
        $cat2->setLabel('label');
        $this->assertTrue($cat->equals($cat2));
    }

    public function testEqualsAssociation() {
        $parent = new Category();
        $parent->setReference('Parent');
        $parent->setLabel('Parent');
        $this->dao->write($parent);
        $this->dao->commit();
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $cat->setParent($parent);
        $cat2 = new Category();
        $cat2->setReference('Ref');
        $cat2->setLabel('label');
        $cat2->setParent($parent);
        $this->assertTrue($cat->equals($cat2));
    }

    public function testUnequalsNull() {
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $this->assertFalse($cat->equals(null));
    }

    public function testUnequalsModel() {
        $cat = new Category();
        $prd = new \Pasteque\Server\Model\Product();
        $this->assertFalse($cat->equals($prd));
    }

    public function testUnequalsSimple() {
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $cat2 = new Category();
        $cat2->setReference('Ref');
        $cat2->setLabel('label2');
        $this->assertFalse($cat->equals($cat2));
    }

    public function testUnequalsAssociationNull() {
        $parent = new Category();
        $parent->setReference('Parent');
        $parent->setLabel('Parent');
        $this->dao->write($parent);
        $this->dao->commit();
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $cat->setParent($parent);
        $cat2 = new Category();
        $cat2->setReference('Ref');
        $cat2->setLabel('label');
        $cat2->setParent(null);
        $this->assertFalse($cat->equals($cat2));
    }

    public function testUnequalsAssociation() {
        $parent = new Category();
        $parent->setReference('Parent');
        $parent->setLabel('Parent');
        $parent2 = new Category();
        $parent2->setReference('Parent2');
        $parent2->setLabel('Parent2');
        $this->dao->write($parent);
        $this->dao->write($parent2);
        $this->dao->commit();
        $cat = new Category();
        $cat->setReference('Ref');
        $cat->setLabel('label');
        $cat->setParent($parent);
        $cat2 = new Category();
        $cat2->setReference('Ref');
        $cat2->setLabel('label');
        $cat2->setParent($parent2);
        $this->assertFalse($cat->equals($cat2));
    }
}
