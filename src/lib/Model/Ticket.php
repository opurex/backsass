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
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\CommonAPI\VersionAPI;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Ticket
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="tickets", uniqueConstraints={@UniqueConstraint(name="ticket_index", columns={"cashregister_id", "number"})})
 */
class Ticket extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [
                new IntField('sequence'),
                new IntField('number'),
                new DateField('date'),
                new IntField('custCount', ['nullable' => true]),
                new FloatField('price', ['nullable' => true]),
                new FloatField('taxedPrice', ['nullable' => true]),
                new FloatField('discountRate'),
                new FloatField('finalPrice', ['nullable' => true]),
                new FloatField('finalTaxedPrice', ['nullable' => true]),
                new FloatField('custBalance')
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'cashRegister',
                 'class' => '\Pasteque\Server\Model\CashRegister'
                 ],
                [
                 'name' => 'user',
                 'class' => '\Pasteque\Server\Model\User'
                 ],
                [
                 'name' => 'lines',
                 'class' => '\Pasteque\Server\Model\TicketLine',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' => 'taxes',
                 'class' => '\Pasteque\Server\Model\TicketTax',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' => 'payments',
                 'class' => '\Pasteque\Server\Model\TicketPayment',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' =>'customer',
                 'class' => '\Pasteque\Server\Model\Customer',
                 'null' => true
                 ],
                [
                 'name' => 'tariffArea',
                 'class' => '\Pasteque\Server\Model\TariffArea',
                 'null' => true
                 ],
                [
                 'name' => 'discountProfile',
                 'class' => '\Pasteque\Server\Model\DiscountProfile',
                 'null' => true
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return 'id';
    }
    /** Tickets don't have reference yet. This is the same as getId(). */
    public function getReference() {
        return $this->getId();
    }

    public function __construct() {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
        $this->taxes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Internal Id of the ticket. Required to link lines and payments.
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /** Get the business id, which values are unique. */
    public function getDictId() {
        return ['cashRegister' => $this->cashRegister->getId(),
                'sequence' => $this->sequence,
                'number' => $this->number];
    }

    /**
     * Id of a cash register
     * @var int
     * @SWG\Property(format="int32")
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\CashRegister")
     * @JoinColumn(name="cashregister_id", referencedColumnName="id", nullable=false)
     */
    protected $cashRegister;
    public function getCashRegister() { return $this->cashRegister; }
    public function setCashRegister($cashRegister) {
        $this->cashRegister = $cashRegister;
    }

    /**
     * Number of the session's cash register
     * @var int
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    protected $sequence;
    public function getSequence() { return $this->sequence; }
    public function setSequence($sequence) { $this->sequence = $sequence; }

    /**
     * Number of the ticket inside the session.
     * @var int
     * @SWG\Property()
     * @Column(type="integer")
     */
    protected $number;
    public function getNumber() { return $this->number; }
    public function setNumber($number) { $this->number = $number; }

    /**
     * Operator
     * @var string
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\User")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;
    public function getUser() { return $this->user; }
    public function setUser($user) { $this->user = $user; }

    /**
     * Payment date, as timestamp.
     * @var date
     * @SWG\Property()
     * @Column(type="datetime")
     */
    protected $date;
    public function getDate() { return $this->date; }
    public function setDate($date) { $this->date = $date; }

    /**
     * Array of line's ticket
     * @var \Pasteque\Ticket[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\TicketLine", mappedBy="ticket", cascade={"persist"}, orphanRemoval=true)
     * @OrderBy({"dispOrder" = "ASC"})
     */
    protected $lines;
    public function getLines() { return $this->lines; }
    public function setLines($lines) {
        $this->lines->clear();
        foreach ($lines as $price) {
            $this->addLine($price);
        }
    }
    public function clearLines() {
        $this->getLines()->clear();
    }
    public function addLine($line) {
        $this->lines->add($line);
        $line->setTicket($this);
    }
    public function removeLine($line) {
        $this->lines->removeElement($line);
        $line->setTicket(null);
    }

    /**
     * Array of tax totals. It holds the final tax base/amount for each
     * tax. That is after all discounts (lines and ticket).
     * @var \Pasteque\Ticket[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\TicketTax", mappedBy="ticket", cascade={"persist"}, orphanRemoval=true)
     */
    protected $taxes;
    public function getTaxes() { return $this->taxes; }
    public function setTaxes($taxes) {
        $this->taxes->clear();
        foreach ($taxes as $tax) {
            $this->addTax($tax);
        }
    }
    public function clearTaxes() {
        $this->getTaxes()->clear();
    }
    public function addTax($tax) {
        $this->taxes->add($tax);
        $tax->setTicket($this);
    }
    public function removeTax($tax) {
        $this->taxes->removeElement($tax);
        $tax->setTicket(null);
    }

    /**
     * Array of payment
     * @var \Pasteque\Payment[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\TicketPayment", mappedBy="ticket", cascade={"persist"}, orphanRemoval=true)
     * @OrderBy({"dispOrder" = "ASC"})
     */
    protected $payments;
public function getPayments() { return $this->payments; }
    public function setPayments($payments) {
        $this->payments->clear();
        foreach ($payments as $payment) {
            $this->addPayment($payment);
        }
    }
    public function clearPayments() {
        $this->getPayments()->clear();
    }
    public function addPayment($payment) {
        $this->payments->add($payment);
        $payment->setTicket($this);
    }
    public function removePayment($payment) {
        $this->payments->removeElement($payment);
        $payment->setTicket(null);
    }

    /**
     * Id of the customer
     * @var string
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Customer")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;
    public function getCustomer() { return $this->customer; }
    public function setCustomer($customer) { $this->customer = $customer; }

    /**
     * Number of customers in the ticket.
     * @var int
     * @SWG\Property()
     * @Column(type="integer", nullable=true)
     */
    protected $custCount;
    public function getCustCount() { return $this->custCount; }
    public function setCustCount($custCount) { $this->custCount = $custCount; }

    /**
     *
     * @var string
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\TariffArea")
     * @JoinColumn(name="tariffarea_id", referencedColumnName="id")
     */
    protected $tariffArea;
    public function getTariffArea() { return $this->tariffArea; }
    public function setTariffArea($tariffArea) {
        $this->tariffArea = $tariffArea;
    }

    /**
     * Rate of discount.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $discountRate = 0.0;
    public function getDiscountRate() { return $this->discountRate; }
    public function setDiscountRate($discountRate) {
        $this->discountRate = $discountRate;
    }

    /**
     * Informative discount profile. The actual discount is set in discountRate.
     * @var string
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\DiscountProfile")
     * @JoinColumn(name="discountprofile_id", referencedColumnName="id")
     */
    protected $discountProfile;
    public function getDiscountProfile() { return $this->discountProfile; }
    public function setDiscountProfile($discountProfile) {
        $this->discountProfile = $discountProfile;
    }

    /**
     * Price without taxes nor ticket discount.
     * It is null when taxedPrice is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $price = null;
    public function getPrice() {
        if ($this->price === null) { return null; }
        else { return round($this->price, 2); }
    }
    public function setPrice($price) {
        if ($price === null) { $this->price = null; }
        else { $this->price = round($price, 2); }
    }

    /**
     * Price without taxes nor ticket discount.
     * It is null when price is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $taxedPrice = null;
    public function getTaxedPrice() {
        if ($this->taxedPrice === null) { return null; }
        else { return round($this->taxedPrice, 2); }
    }
    public function setTaxedPrice($taxedPrice) {
        if ($taxedPrice === null) { $this->taxedPrice = null; }
        else { $this->taxedPrice = round($taxedPrice, 2); }
    }

    /**
     * Total price without taxes with discount rate.
     * It is never null.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $finalPrice;
    public function getFinalPrice() {
        return round($this->finalPrice, 2);
    }
    public function setFinalPrice($finalPrice) {
        $this->finalPrice = round($finalPrice, 2);
    }

    /**
     * Total price with taxes and discount rate.
     * It is never null.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $finalTaxedPrice;
    public function getFinalTaxedPrice() {
        return round($this->finalTaxedPrice, 2);
    }
    public function setFinalTaxedPrice($finalTaxedPrice) {
        $this->finalTaxedPrice = round($finalTaxedPrice, 2);
    }

    /**
     * Changes in the customer's balance.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $custBalance = 0.0;
    public function getCustBalance() {
        return round($this->custBalance, 2);
    }
    public function setCustBalance($custBalance) {
        $this->custBalance = round($custBalance, 2);
    }

    public function toStruct() {
        $struct = parent::toStruct();
        $struct['date'] = DateUtils::toTimestamp($this->getDate());
        return $struct;
    }

    /** Create struct of the full data to be written in stone
     * (that is, FiscalTicket). */
    public function toStone() {
        $struct = $this->toStruct();
        // Include source version in case it has to be parsed.
        $struct['version'] = VersionAPI::VERSION;
        // Format date in human-readable format
        $struct['date'] = $this->getDate()->format('Y-m-d H:i:s');
        // Fetch associative fields and include the data.
        unset($struct['id']);
        $struct['cashRegister'] = ['reference' => $this->getCashRegister()->getReference(),
                'label' => $this->getCashRegister()->getLabel()];
        $struct['user'] = $this->getUser()->getName();
        for ($i = 0; $i < count($struct['lines']); $i++) {
            // Lines already has all the unlinked values.
            // Just delete references.
            $line = $struct['lines'][$i];
            unset($line['id']);
            unset($line['ticket']);
            unset($line['product']);
            unset($line['tax']);
            $struct['lines'][$i] = $line;
        }
        for ($i = 0; $i < count($struct['taxes']); $i++) {
            $tax = $struct['taxes'][$i];
            unset($tax['id']);
            unset($tax['ticket']);
            unset($tax['tax']);
            $struct['taxes'][$i] = $tax;
        }
        for ($i = 0; $i < count($struct['payments']); $i++) {
            $payment = $struct['payments'][$i];
            unset($payment['id']);
            unset($payment['ticket']);
            $paymentMode = $this->getPayments()->get($i)->getPaymentMode();
            $currency = $this->getPayments()->get($i)->getCurrency();
            $payment['paymentMode'] = ['reference' =>$paymentMode->getReference(),
                    'label' => $paymentMode->getLabel()];
            $payment['currency'] = ['reference' => $currency->getReference(),
                    'label' => $currency->getLabel()];
            $struct['payments'][$i] = $payment;
        }
        if ($this->getCustomer() !== null) {
            // Delete all non-identification fields
            $c = $this->getCustomer()->toStruct();
            unset($c['id']);
            unset($c['maxDebt']);
            unset($c['currDebt']);
            unset($c['debtDate']);
            unset($c['prepaid']);
            unset($c['note']);
            unset($c['visible']);
            unset($c['hasImage']);
            unset($c['expireDate']);
            $struct['customer'] = $c;
        }
        if ($this->getTariffArea() !== null) {
            $struct['tariffArea'] = ['reference' => $this->getTariffArea()->getReference(),
                    'label' => $this->getTariffArea()->getLabel()];
        }
        if ($this->getDiscountProfile() !== null) {
            $struct['discountProfile'] =  $this->getDiscountProfile()->getLabel();
        }
        return $struct;
    }
}
