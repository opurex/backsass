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
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class PaymentModeValue. This is an embedded class.
 * This are predefined values a payment mode can have such as coin values
 * or frequent coupon value.
 * These are just helpers, it does not prevent the payment mode to have
 * an arbitrary set value.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="paymentmodevalues")
 */
class PaymentModeValue extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new FloatField('value'), 'hasImage'];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getParentFieldName() {
        return 'paymentMode';
    }

    public function getId() {
        if ($this->getPaymentMode() === null) {
            return ['paymentMode' => null, 'value' => $this->getValue()];
        } else {
            return ['paymentMode' => $this->getPaymentMode()->getId(),
                    'value' =>$this->getValue()];
        }
    }

    /**
     * @var integer
     * @SWG\Property()
     * @Id
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\PaymentMode", inversedBy="values")
     * @JoinColumn(name="paymentmode_id", referencedColumnName="id", nullable=false)
     */
    protected $paymentMode;
    public function getPaymentMode() { return $this->paymentMode; }
    public function setPaymentMode($paymentMode) { $this->paymentMode = $paymentMode; }

    /**
     * @var float
     * @SWG\Property()
     * @Id
     * @Column(type="float")
     */
    protected $value;
    public function getValue() { return round($this->value, 5); }
    public function setValue($value) { $this->value = round($value, 5); }

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
}
