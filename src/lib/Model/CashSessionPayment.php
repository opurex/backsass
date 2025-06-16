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

use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\System\DAO\DAO;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class CashSessionPayment
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="sessionpayments")
 */
class CashSessionPayment extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new FloatField('amount'), new FloatField('currencyAmount')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'paymentMode',
                 'class' => '\Pasteque\Server\Model\PaymentMode'
                 ],
                [
                 'name' => 'currency',
                 'class' => '\Pasteque\Server\Model\Currency'
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'cashSession';
    }

    public function getId() {
        if ($this->getCashSession() === null) {
            return ['cashSession' => null,
                    'paymentMode' => $this->getPaymentMode()->getId(),
                    'currency' => $this->getCurrency()->getId()];
        } else {
            return ['cashSession' => $this->getCashSession()->getId(),
                'paymentMode' => $this->getPaymentMode()->getId(),
                'currency' => $this->getCurrency()->getId()];
        }
    }

    /**
     * @var integer
     * @SWG\Property
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\CashSession", inversedBy="payments")
     * @JoinColumn(name="cashsession_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $cashSession;
    public function getCashSession() { return $this->cashSession; }
    public function setCashSession($cashSession) { $this->cashSession = $cashSession; }

    /**
     * Type of the Payment
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\PaymentMode")
     * @JoinColumn(name="paymentmode_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $paymentMode;
    public function getPaymentMode() { return $this->paymentMode; }
    public function setPaymentMode($paymentMode) { $this->paymentMode = $paymentMode; }

    /**
     * Id of the Currency of the Payment
     * @var integer
     * @SWG\Property(format="int32")
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Currency")
     * @JoinColumn(name="currency_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $currency;
    public function getCurrency() { return $this->currency; }
    public function setCurrency($currency) { $this->currency = $currency; }

    /**
     * Amount of the payment in the main currency
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float")
     */
    protected $amount;
    public function getAmount() { return round($this->amount, 5); }
    public function setAmount($amount) {
        $this->amount = round($amount, 5);
    }

    /**
     * Amount of the Payment in the used Currency
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float")
     */
    public $currencyAmount;
    public function getCurrencyAmount() {
        return round($this->currencyAmount, 5);
    }
    public function setCurrencyAmount($currencyAmount) {
        $this->currencyAmount = round($currencyAmount, 5);
    }

    public static function load($struct, $parentRecord, $dao) {
        $struct = PaymentMode::convertIdFromType($struct, $dao);
        return parent::load($struct, $parentRecord, $dao);
    }

    public function merge($struct, $dao) {
        $struct = PaymentMode::convertIdFromType($struct, $dao);
        parent::merge($struct, $dao);
    }
}
