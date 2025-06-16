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
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DAO;
use \Pasteque\Server\System\DAO\DoctrineEmbeddedModel;

/**
 * Class CashSessionCatTax. Sum of the cs amount by category by tax.
 * This class is for fast data analysis only.
 * For declarations see FiscalTicket.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="sessioncattaxes")
 */
class CashSessionCatTax extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        // Not associative to be able to delete empty categories.
        return [
                new StringField('reference'),
                new StringField('label'),
                new FloatField('base'),
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
        return 'cashSession';
    }

    public function getId() {
        if ($this->getCashSession() === null) {
            return ['cashSession' => null,
                    'reference' => $this->getReference(),
                    'tax' => $this->getTax()->getId()];
        } else {
            return ['cashSession' => $this->getCashSession()->getId(),
                    'reference' => $this->getReference(),
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
     * Code of the category, user-friendly ID.
     * It is automatically set from label if not explicitely set.
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     * @Id
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($ref) { $this->reference = $ref; }

    /**
     * Label of the category
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) {
        $this->label = $label;
        if ($this->getReference() === null) {
            $this->setReference($label);
        }
    }

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
    }

    /**
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $base;
    public function getBase() { return round($this->base, 5); }
    public function setBase($base) {
        $this->base = round($base, 5);
    }

    /**
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $amount;
    public function getAmount() { return round($this->amount, 5); }
    public function setAmount($amount) {
        $this->amount = round($amount, 5);
    }

}
