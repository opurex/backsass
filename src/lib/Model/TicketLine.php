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
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DAO;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class TicketLine. This class is for fast data analysis only.
 * For declarations see FiscalTicket.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="ticketlines")
 */
class TicketLine extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [
                new IntField('dispOrder'),
                new StringField('productLabel', ['autosetFrom' => 'product']),
                new FloatField('unitPrice', ['nullable' => true]),
                new FloatField('taxedUnitPrice', ['nullable' => true]),
                new FloatField('quantity'),
                new FloatField('price', ['nullable' => true]),
                new FloatField('taxedPrice', ['nullable' => true]),
                new FloatField('taxRate'),
                new FloatField('discountRate'),
                new FloatField('finalPrice', ['nullable' => true]),
                new FloatField('finalTaxedPrice', ['nullable' => true])
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'product',
                 'class' => '\Pasteque\Server\Model\Product',
                 'null' => true
                 ],
                [
                 'name' => 'tax',
                 'class' => '\Pasteque\Server\Model\Tax',
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'ticket';
    }

    public function getId() {
        if ($this->getTicket() === null) {
            return ['ticket' => null, 'dispOrder' => $this->getDispOrder()];
        } else {
            return ['ticket' => $this->getTicket()->getId(),
                    'dispOrder' => $this->getDispOrder()];
        }
    }

    /**
     * @var integer
     * @SWG\Property
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Ticket", inversedBy="lines")
     * @JoinColumn(name="ticket_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $ticket;
    public function getTicket() { return $this->ticket; }
    public function setTicket($ticket) { $this->ticket = $ticket; }

    /**
     * Display order or number of the line
     * @var integer
     * @SWG\Property()
     * @Column(type="integer")
     * @Id
     */
    protected $dispOrder;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

    /**
     * Id of the product
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Product")
     * @JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;
    public function getProduct() { return $this->product; }
    /** Set the product. If productLabel is null, it will be set with
     * the label of the product. */
    public function setProduct($product) {
        $this->product = $product;
        if ($product !== null && $this->getProductLabel() === null) {
            $this->setProductLabel($product->getLabel());
        }
    }

    /**
     * Label of product at the ticket time (or when product is null)
     * @var string null
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $productLabel;
    public function getProductLabel() { return $this->productLabel; }
    public function setProductLabel($productLabel) {
        $this->productLabel = $productLabel;
    }

    /**
     * Unit price without tax and before applying discount.
     * It is null when taxedUnitPrice is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $unitPrice = null;
    public function getUnitPrice() {
        if ($this->unitPrice === null) { return null; }
        else { return round($this->unitPrice, 5); }
    }
    public function setUnitPrice($unitPrice) {
        if ($unitPrice === null) { $this->unitPrice = null; }
        else { $this->unitPrice = round($unitPrice, 5); }
    }

/**
     * Unit price with tax and before applying discount.
     * It is null when unitPrice is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $taxedUnitPrice = null;
    public function getTaxedUnitPrice() {
        if ($this->taxedUnitPrice === null) { return null; }
        else { return round($this->taxedUnitPrice, 5); }
    }
    public function setTaxedUnitPrice($taxedUnitPrice) {
        if ($taxedUnitPrice === null) { $this->taxedUnitPrice = null; }
        else { $this->taxedUnitPrice = round($taxedUnitPrice, 5); }
    }

    /**
     * Quantity of product on this ticket line
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $quantity = 1.0;
    public function getQuantity() { return round($this->quantity, 5); }
    public function setQuantity($quantity) {
        $this->quantity = round($quantity, 5);
    }

    /**
     * In quantity price without tax and before applying discount.
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
     * In quantity price with tax and before applying discount.
     * It is null when price is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
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
     * Id of taxe on the ticket line
     * @var float
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Tax")
     * @JoinColumn(name="tax_id", referencedColumnName="id", nullable=false)
     */
    protected $tax;
    public function getTax() { return $this->tax; }
    public function setTax($tax) { $this->tax = $tax; }

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
     * Rate of discount on this ticket line
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $discountRate = 0.0;
    public function getDiscountRate() { return $this->discountRate; }
    public function setDiscountRate($discountRate) { $this->discountRate = $discountRate; }

    /**
     * Total price without taxes with discount rate.
     * It is null when finalTaxedPrice is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $finalPrice = null;
    public function getFinalPrice() {
        if ($this->finalPrice === null) { return null; }
        else { return round($this->finalPrice, 2); }
    }
    public function setFinalPrice($finalPrice) {
        if ($finalPrice === null) { $this->finalPrice = null; }
        else { $this->finalPrice = round($finalPrice, 2); }
    }

    /**
     * Total price with taxes and discount rate.
     * It is null when finalPrice is set.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $finalTaxedPrice = null;
    public function getFinalTaxedPrice() {
        if ($this->finalTaxedPrice === null) { return null; }
        else { return round($this->finalTaxedPrice, 2); }
    }
    public function setFinalTaxedPrice($finalTaxedPrice) {
        if ($finalTaxedPrice === null) { $this->finalTaxedPrice = null; }
        else { $this->finalTaxedPrice = round($finalTaxedPrice, 2); }
    }

    /**
     * @Override from DoctrineModel
     * Merge $struct within this record. Same as a regular merge with a
     * post-check that a product label is not null in the end.
     * Prefer no label (though it should be avoided) than rejecting it.
     */
    public function merge($struct, $dao) {
        parent::merge($struct, $dao);
        if ($this->getProductLabel() === null) {
            $this->setProductLabel('');
        }
    }
}
