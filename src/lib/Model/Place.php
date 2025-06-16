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

use \Pasteque\Server\Model\Floor;
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class Place
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="places")
 */
class Place extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [
                new StringField('label'),
                new IntField('x', ['rounding' => true]),
                new IntField('y', ['rounding' => true])
                ];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getParentFieldName() {
        return 'floor';
    }

    /**
     * ID of the place
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Label of the place
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    /**
     * Position X for draw.
     * @var int x
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    protected $x = 0;
    public function getX() { return $this->x; }
    public function setX($x) { $this->x = $x; }

    /**
     * Position Y for draw.
     * @var int y
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    protected $y = 0;
    public function getY() { return $this->y; }
    public function setY($y) { $this->y = $y; }

    /**
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Floor", inversedBy="places")
     * @JoinColumn(name="floor_id", referencedColumnName="id", nullable=false)
     */
    protected $floor;
    public function getFloor() { return $this->floor; }
    public function setFloor($floor) { $this->floor = $floor; }

}
