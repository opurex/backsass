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
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class CompositionProduct
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="compositionproducts")
 */
class CompositionProduct extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new IntField('dispOrder')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'product',
                 'class' => '\Pasteque\Server\Model\Product',
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'compositionGroup';
    }

    public function getId() {
        if ($this->getCompositionGroup() === null) {
            return ['compositionGroup' => null,
                    'product' => $this->getProduct()->getId()];
        } else {
            return ['compositionGroup' => $this->getCompositionGroup()->getId(),
                    'product' => $this->getProduct()->getId()];
        }
    }

    /**
     * @var integer
     * @SWG\Property()
     * @Id
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\CompositionGroup", inversedBy="compositionProducts")
     * @JoinColumn(name="compositiongroup_id", referencedColumnName="id", nullable=false)
     */
    protected $compositionGroup;
    public function getCompositionGroup() { return $this->compositionGroup; }
    public function setCompositionGroup($compositionGroup) {
        $this->compositionGroup = $compositionGroup;
    }

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
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Product")
     * @Id @JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    protected $product;
    public function getProduct() { return $this->product; }
    public function setProduct($product) { $this->product = $product; }

}
