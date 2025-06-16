<?php
//    Pastèque API
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
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

use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class TariffAreaPrice
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="tariffareaprices")
 */
class TariffAreaPrice extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new FloatField('price', ['nullable' => true])];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'product',
                 'class' => '\Pasteque\Server\Model\Product',
                 ],
                [
                 'name' => 'tax',
                 'class' => '\Pasteque\Server\Model\Tax',
                 'null' => true // when price is set, see isUsefull()
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'tariffArea';
    }

    public function getId() {
        if ($this->getTariffArea() === null) {
            return ['tariffArea' => null,
                    'product' => $this->getProduct()->getId()];
        } else {
            return ['tariffArea' => $this->getTariffArea()->getId(),
                'product' =>$this->getProduct()->getId()];
        }
    }

    /**
     * @var integer
     * @SWG\Property()
     * @Id
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\TariffArea", inversedBy="prices")
     * @JoinColumn(name="tariffarea_id", referencedColumnName="id", nullable=false)
     */
    protected $tariffArea;
    public function getTariffArea() { return $this->tariffArea; }
    public function setTariffArea($tariffArea) { $this->tariffArea = $tariffArea; }

    /**
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Product")
     * @Id @JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    protected $product;
    public function getProduct() { return $this->product; }
    public function setProduct($product) { $this->product = $product; }

    /** New sell price without taxes if changed. Null if it is as the original.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $price = null;
    public function getPrice() {
        return ($this->price === null) ? null : round($this->price, 5);
    }
    public function setPrice($price) {
        $this->price = ($price === null) ? null : round($price, 5);
    }

    /**
     * New tax if changed. Null if it is as the original.
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Tax")
     * @JoinColumn(name="tax_id", referencedColumnName="id", nullable=true)
     */
    protected $tax;
    public function getTax() { return $this->tax; }
    public function setTax($tax) { $this->tax = $tax; }

    /** Check if the price holds usefull data:
     * either an alternative price, tax or both. */
    public function isUsefull() {
        return ($this->getTax() !== null || $this->getPrice() !== null);
    }

}
