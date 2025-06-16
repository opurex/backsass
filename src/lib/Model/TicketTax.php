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

use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\System\DAO\DAO;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class TicketTax. Sum of the taxes amount by tax, including ticket discount.
 * This class is for fast data analysis only.
 * For declarations see FiscalTicket.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="tickettaxes")
 */
class TicketTax extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [
                new FloatField('base'),
                new FloatField('taxRate', ['autosetFrom' => 'tax']),
                new FloatField('amount')
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
        return 'ticket';
    }

    public function getId() {
        if ($this->getTicket() === null) {
            return ['ticket' => null, 'tax' => $this->getTax()->getId()];
        } else {
            return ['ticket' => $this->getTicket()->getId(),
                'tax' => $this->getTax()->getId()];
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
     * @Column(type="float")
     */
    protected $base;
    public function getBase() { return round($this->base, 2); }
    public function setBase($base) {
        $this->base = round($base, 2);
    }

    /**
     * Total amount of tax.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $amount;
    public function getAmount() { return round($this->amount, 2); }
    public function setAmount($amount) {
        $this->amount = round($amount, 2);
    }

}
