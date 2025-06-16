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
use \Pasteque\Server\System\DAO\DAO;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class CashSessionTax. Sum of the taxes amount by tax.
 * This class is for fast data analysis only.
 * For declarations see FiscalTicket.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="sessiontaxes")
 */
class CashSessionTax extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [
                new FloatField('taxRate', ['autosetFrom' => 'tax']),
                new FloatField('base'),
                new FloatField('basePeriod'),
                new FloatField('baseFYear'),
                new FloatField('amount'),
                new FloatField('amountPeriod'),
                new FloatField('amountFYear')
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'tax',
                 'class' => '\Pasteque\Server\Model\Tax',
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'cashSession';
    }

    public function getId() {
        if ($this->getCashSession() === null) {
            return ['cashSession' => null,
                    'tax' => $this->getTax()->getId()];
        } else {
            return ['cashSession' => $this->getCashSession()->getId(),
                    'tax' => $this->getTax()->getId()];
        }
    }

    /**
     * @var integer
     * @SWG\Property
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\CashSession", inversedBy="taxes")
     * @JoinColumn(name="cashsession_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $cashSession;
    public function getCashSession() { return $this->cashSession; }
    public function setCashSession($cashSession) { $this->cashSession = $cashSession; }

    /**
     * Id of the tax
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Tax")
     * @JoinColumn(name="tax_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $tax;
    public function getTax() { return $this->tax; }
    /** Set the tax. If taxRate is null, it will be set with
     * the rate of the tax. */
    public function setTax($tax) {
        $this->tax = $tax;
        if ($this->getTaxRate() == null) {
            $this->setTaxRate($tax->getRate());
        }
    }

    /**
     * Rate of the tax at the time of the ticket
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $taxRate;
    public function getTaxRate() { return $this->taxRate; }
    public function setTaxRate($taxRate) { $this->taxRate = $taxRate; }

    /**
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=false)
     */
    protected $base = 0.0;
    public function getBase() { return round($this->base, 5); }
    public function setBase($base) {
        $this->base = round($base, 5);
    }

    /**
     * Tax base total by period.
     * @var float
     * @SWG\Property(format="double", nullable=false)
     * @Column(type="float", nullable=false)
     */
    protected $basePeriod = 0.0;
    public function getBasePeriod() {
        return round($this->basePeriod, 5);
    }
    public function setBasePeriod($basePeriod) {
            $this->basePeriod = round($basePeriod, 5);
    }

    /**
     * Tax base total by fiscal year.
     * @var float
     * @SWG\Property(format="double", nullable=false)
     * @Column(type="float", nullable=false)
     */
    protected $baseFYear = 0.0;
    public function getBaseFYear() {
        return round($this->baseFYear, 5);
    }
    public function setBaseFYear($baseFYear) {
            $this->baseFYear = round($baseFYear, 5);
    }

    /**
     * Total amount of tax.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=false)
     */
    protected $amount = 0.0;
    public function getAmount() { return round($this->amount, 5); }
    public function setAmount($amount) {
        $this->amount = round($amount, 5);
    }

    /**
     * Tax amount total by period.
     * @var float
     * @SWG\Property(format="double", nullable=false)
     * @Column(type="float", nullable=false)
     */
    protected $amountPeriod = 0.0;
    public function getAmountPeriod() {
        return round($this->amountPeriod, 5);
    }
    public function setAmountPeriod($amountPeriod) {
            $this->amountPeriod = round($amountPeriod, 5);
    }

    /**
     * Tax amount total by fiscal year.
     * @var float
     * @SWG\Property(format="double", nullable=false)
     * @Column(type="float", nullable=false)
     */
    protected $amountFYear = 0.0;
    public function getAmountFYear() {
        return round($this->amountFYear, 5);
    }
    public function setAmountFYear($amountFYear) {
            $this->amountFYear = round($amountFYear, 5);
    }
}
