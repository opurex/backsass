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

use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class TariffArea.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="tariffareas")
 */
class TariffArea extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [
                new StringField('reference'),
                new StringField('label'),
                new IntField('dispOrder')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'prices',
                 'class' => '\Pasteque\Server\Model\TariffAreaPrice',
                 'array' => true,
                 'embedded' => true
                 ],
                ];
    }
    protected static function getReferenceKey() {
        return 'reference';
    }

    public function __construct() {
        $this->prices = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * ID of the area
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Code of the area, user-friendly ID.
     * It is automatically set from label if not explicitely set.
     * @var string
     * @SWG\Property()
     * @Column(type="string", unique=true)
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($reference) { $this->reference = $reference; }


    /**
     * name of the area
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
     * Order of display inside it's category
     * @var int order
     * @SWG\Property(format="int32")
     * @Column(type="integer", name="disp_order")
     */
    protected $dispOrder = 0;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

    /**
     * @OneToMany(targetEntity="\Pasteque\Server\Model\TariffAreaPrice", mappedBy="tariffArea", cascade={"all"}) */
    // Warning: no orphan removal as it doesn't work with Mysql. This is handled in TariffAreaAPI.
    protected $prices;
    public function getPrices() { return $this->prices; }
    public function setPrices($prices) {
        $this->clearPrices();
        foreach ($prices as $price) {
            $this->addPrice($price);
        }
    }
    public function clearPrices() {
        foreach ($this->prices->getKeys() as $key) {
            $this->prices->remove($key);
        }
    }
    /** Add a TariffAreaPrice if not useless. */
    public function addPrice($price) {
        if (!$price->isUsefull()) { return; }
        $this->prices->add($price);
        $price->setTariffArea($this);
    }
    public function removePrice($price) {
        $this->prices->removeElement($price);
    }

}
