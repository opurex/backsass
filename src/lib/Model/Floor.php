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
 * Class Floor
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="floors")
 */
class Floor extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return ['id', new StringField('label'), new IntField('dispOrder')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'places',
                 'class' => '\Pasteque\Server\Model\Place',
                 'array' => true,
                 'embedded' => true
                 ],
                ];
    }
    protected static function getReferenceKey() {
        return 'id';
    }
    /** Floors don't have reference yet. This is the same as getId(). */
    public function getReference() {

    }

    public function __construct() {
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * ID of the floor
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Label of the floor
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    /**
     * Floor image if any.
     * @var binary
     * @SWG\Property()
     * @Column(type="blob", nullable=true)
     */
    protected $image;
    public function getImage() { return $this->image; }
    public function setImage($image) { $this->image = $image; }

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
     * @OneToMany(targetEntity="\Pasteque\Server\Model\Place", mappedBy="floor", cascade={"all"}, orphanRemoval=true) */
    protected $places;
    public function getPlaces() { return $this->places; }
    public function setPlaces($places) {
        $this->clearPlaces();
        foreach ($places as $place) {
            $this->addPlace($place);
        }
    }
    public function clearPlaces() {
        foreach ($this->places->getKeys() as $key) {
            $this->places->get($key)->setFloor(null);
            $this->places->remove($key);
        }
    }
    public function addPlace($place) {
        $this->places->add($place);
        $place->setFloor($this);
    }
    public function removePlace($place) {
        $this->places->removeElement($place);
        $place->setFloor(null);
    }

}
