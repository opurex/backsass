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
 * Class PaymentModeReturn
 * This defines how payment with a higher value than requested are handled.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="paymentmodereturns")
 */
class PaymentModeReturn extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new FloatField('minAmount')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'returnMode',
                 'class' => '\Pasteque\Server\Model\PaymentMode',
                 'null' => false
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'paymentMode';
    }

    public function getId() {
        if ($this->getPaymentMode() === null) {
            return ['paymentMode' => null,
                    'minAmount' => $this->getMinAmount()];
        } else {
            return ['paymentMode' => $this->getPaymentMode()->getId(),
                    'minAmount' =>$this->getMinAmount()];
        }
    }

    /**
     * @var integer
     * @SWG\Property()
     * @Id
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\PaymentMode", inversedBy="returns")
     * @JoinColumn(name="paymentmode_id", referencedColumnName="id", nullable=false)
     */
    protected $paymentMode;
    public function getPaymentMode() { return $this->paymentMode; }
    /** Set the payment mode. For new PaymentMode when the id is not already set
     * it also set returnMode when null.
     * That means you can create a self referencing structure without knowing
     * the PaymentMode id (which doesn't exists on create)
     */
    public function setPaymentMode($paymentMode) {
        $this->paymentMode = $paymentMode;
        if ($this->getReturnMode() === null) {
            $this->setReturnMode($paymentMode);
        }
    }

    /**
     * @var float
     * @SWG\Property()
     * @Id
     * @Column(type="float")
     */
    protected $minAmount = 0.0;
    public function getMinAmount() { return $this->minAmount; }
    public function setMinAmount($minAmount) { $this->minAmount = $minAmount; }

    /**
     * Can be null when creating a new return for a new PaymentMode.
     * In that case, when assigning the return mode to a payment mode,
     * it will then be linked to it.
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\PaymentMode")
     * @JoinColumn(name="returnmode_id", referencedColumnName="id", nullable=false)
     */
    protected $returnMode;
    public function getReturnMode() { return $this->returnMode; }
    public function setReturnMode($returnMode) { $this->returnMode = $returnMode; }

    /** @Override from DoctrineModel */
    protected function associationEquals($o, $field) {
        if ($field['name'] != 'returnMode') {
            return parent::associationEquals($o, $field);
        }
        // Special check to break infinite loop in return mode
        $thisVal = $this->getReturnMode();
        $oVal = $o->getReturnMode();
        return ($thisVal->getReference() == $oVal->getReference());
    }
}
