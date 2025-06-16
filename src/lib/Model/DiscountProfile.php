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

use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class DiscountProfile
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="discountprofiles")
 */
class DiscountProfile extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [new StringField('label'),
                new FloatField('rate')];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getReferenceKey() {
        return 'id';
    }
    /**
     * DiscountProfiles don't have reference yet.
     * This is the same as getId().
     */
    public function getReference() {
        return $this->getId();
    }

    /**
     * ID of the discount profile
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Label of the Discount Profile
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    /**
     * Rate of the discount
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float")
     */
    private $rate;
    public function getRate() { return $this->rate; }
    public function setRate($rate) { $this->rate = $rate; }

}
