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
 * Class CashSessionCustBalance. Sum of the balance update amount by customer.
 * This class is for fast data analysis only.
 * For declarations see FiscalTicket.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="sessioncustbalances")
 */
class CashSessionCustBalance extends DoctrineEmbeddedModel
{
    protected static function getDirectFieldNames() {
        return [new FloatField('balance')];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'customer',
                 'class' => '\Pasteque\Server\Model\Customer',
                 ]
                ];
    }
    protected static function getParentFieldName() {
        return 'cashSession';
    }

    public function getId() {
        if ($this->getCashSession() === null) {
            return ['cashSession' => null,
                    'customer' => $this->getCustomer()->getId()];
        } else {
            return ['cashSession' => $this->getCashSession()->getId(),
                    'customer' => $this->getCustomer()->getId()];
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
     * Id of the customer
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\Customer")
     * @JoinColumn(name="customer_id", referencedColumnName="id", nullable=false)
     * @Id
     */
    protected $customer;
    public function getCustomer() { return $this->customer; }
    /** Set the customer. */
    public function setCustomer($customer) { $this->customer = $customer; }

    /**
     * Total balance.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $balance;
    public function getBalance() { return round($this->balance, 5); }
    public function setBalance($balance) {
        $this->balance = round($balance, 5);
    }

}
