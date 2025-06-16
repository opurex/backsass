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
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class CompositionGroup
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="compositiongroups")
 */
class CompositionGroup extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new StringField('label'), new IntField('dispOrder')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'compositionProducts',
                 'class' => '\Pasteque\Server\Model\CompositionProduct',
                 'array' => true,
                 'embedded' => true,
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'product';
    }

    public function __construct() {
        $this->compositionProducts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Internal Id.
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Product", inversedBy="compositionGroups")
     * @JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    protected $product;
    public function getProduct() { return $this->product; }
    public function setProduct($product) { $this->product = $product; }

    /**
     * Name of the composition group
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    /**
     * Order of display
     * @var int order
     * @SWG\Property(format="int32")
     * @Column(type="integer", name="disp_order")
     */
    protected $dispOrder = 0;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

    /**
     * Product image if any.
     * @var binary
     * @SWG\Property()
     * @Column(type="blob", nullable=true)
     */
    protected $image;
    public function getImage() { return $this->image; }
    public function setImage($image) { $this->image = $image; }

    /**
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CompositionProduct", mappedBy="compositionGroup", cascade={"persist"}, orphanRemoval=true)
     * @OrderBy({"dispOrder" = "ASC"}) */
    protected $compositionProducts;
    public function getCompositionProducts() { return $this->compositionProducts; }
    public function setCompositionProducts($compositionProducts) {
        $this->compositionProducts->clear();
        foreach ($compositionProducts as $prd) {
            $this->addCompositionProducts($prd);
        }
    }
    public function clearCompositionProducts() {
        $this->getCompositionProducts()->clear();
    }
    public function addCompositionProduct($compositionProduct) {
        $this->compositionProducts->add($compositionProduct);
        $compositionProduct->setCompositionGroup($this);
    }
    public function removeCompositionProduct($prd) {
        $this->compositionProducts->removeElement($prd);
        $prd->setCompositionGroup(null);
    }
}
