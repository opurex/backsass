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

use \Pasteque\Server\Model\Field\DateField;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * 
 * Class Discount
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="discounts")
 */
class Discount extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return ['id',
                new StringField('label'),
                new DateField('startDate', ['nullable' => true]),
                new DateField('endDate', ['nullable' => true]),
                new FloatField('rate'),
                new StringField('barcode'),
                new IntField('barcodeType'),
                new IntField('dispOrder')
                ];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getReferenceKey() {
        return 'id';
    }
    /** Discounts don't have reference yet. This is the same as getId(). */
    public function getReference() {
        return $this->getId();
    }

    /**
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /** Label is broken in v7. It is not stored in database.
     * Until this is fixed, it is generated from other fields. */
    protected $label;
    public function getLabel() {
        $label = sprintf('Remise %d %.2d%%', $this->getId(), $this->getRate());
        if ($this->getStartDate() != null && $this->getEndDate() != null) {
            $label = sprintf("%s (%s/%s)", $label,
                    $this->getStartDate()->format('Y-m-d H:i:s'),
                    $this->getEndDate()->format('Y-m-d H:i:s'));
        }
        return $label;
    }
    public function setLabel() {}

    /**
     * Start date
     * @var string|null
     * @SWG\Property(format="date-time")
     * @Column(type="datetime", nullable=true)
     */
    protected $startDate;
    public function getStartDate() { return $this->startDate; }
    public function setStartDate($startDate) { $this->startDate = $startDate; }

    /**
     * End date
     * @var string|null
     * @SWG\Property(format="date-time")
     * @Column(type="datetime", nullable=true)
     */
    protected $endDate;
    public function getEndDate() { return $this->endDate; }
    public function setEndDate($endDate) { $this->endDate = $endDate; }

    /**
     * Rate of discount
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $rate;
    public function getRate() { return $this->rate; }
    public function setRate($rate) { $this->rate = $rate; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string", nullable=true)
     */
    protected $barcode;
    public function getBarcode() { return $this->barcode; }
    public function setBarcode($barcode) { $this->barcode = $barcode; }

    /**
     * @var integer
     * @SWG\Property()
     * @Column(type="integer")
     */
    protected $barcodeType = 0;
    public function getBarcodeType() { return $this->barcodeType; }
    public function setBarcodeType($type) { $this->barcodeType = $type; }

    /**
     * Order of display
     * @var int order
     * @SWG\Property(format="int32")
     * @Column(type="integer", name="disp_order")
     */
    protected $dispOrder = 0;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

}
