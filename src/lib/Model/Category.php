<?php
//    Pastèque API
//
//    Copyright (C) 2012-2015 Scil (http://scil.coop)
//    Cédric Houbart, Philippe Pary
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\Model;

use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Category
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="categories")
 */
class Category extends DoctrineMainModel
{

    protected static function getDirectFieldNames() {
        return ['id',
                new StringField('reference', ['autosetFrom' => 'label']),
                new StringField('label'),
                new IntField('dispOrder'),
                'hasImage'
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'parent',
                 'class' => '\Pasteque\Server\Model\Category',
                 'null' => true
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return 'reference';
    }

    public function __construct() {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Internal ID of the category for performance issues.
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Code of the category, user-friendly ID.
     * It is automatically set from label if not explicitely set.
     * @var string
     * @SWG\Property()
     * @Column(type="string", unique=true)
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($ref) { $this->reference = $ref; }

    /**
     * Parent's category if any.
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="Category", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id") */
    protected $parent;
    public function getParent() { return $this->parent; }
    public function setParent($parent) { $this->parent = $parent; }

    /** Required by Doctrine. Use parent instead.
     * @OneToMany(targetEntity="Category", mappedBy="parent") */
    protected $children;
    public function getChildren() { return $this->children; }
    public function setChildren($children) { $this->children = $children; }

    /**
     * Label of the category
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) {
        $this->label = $label;
        if ($this->getReference() === null) {
            $this->setReference($label);
        }
    }
    
    /**
     * True if an image can be found for this model.
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $hasImage = false;
    public function getHasImage() { return $this->hasImage; }
    public function hasImage() { return $this->getHasImage(); }
    public function setHasImage($hasImage) { $this->hasImage = $hasImage; }

    /**
     * Order of display of the category
     * @var int order
     * @SWG\Property(format="int32")
     * @Column(type="integer", name="disp_order")
     */
    protected $dispOrder = 0;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

}
