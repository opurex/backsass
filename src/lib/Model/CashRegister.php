<?php
//    Pastèque API
//
//    Copyright (C) 2017 Pastèque Contributors
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
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class CashRegister
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="cashregisters")
 */
class CashRegister extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [
                new StringField('reference'),
                new StringField('label'),
                new IntField('nextTicketId'),
                new IntField('nextSessionId')
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'sessions',
                 'class' => '\Pasteque\Server\Model\CashSession',
                 'array' => true,
                 'internal' => true
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return 'reference';
    }

    /**
     * ID of the cash register
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Code of the cash register, user-friendly ID.
     * It is automatically set from label if not explicitely set.
     * @var string
     * @SWG\Property()
     * @Column(type="string", unique=true)
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($reference) { $this->reference = $reference; }

    /**
     * name of the cash register
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
     * Id (number) of the next Ticket. Starts to 1.
     * @var int
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    public $nextTicketId = 1;
    public function getNextTicketId() { return $this->nextTicketId; }
    public function setNextTicketId($nextTicketId) {
        $this->nextTicketId = $nextTicketId;
    }

    /**
     * Id (number) of the next session. Starts to 1.
     * @var int
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    public $nextSessionId = 1;
    public function getNextSessionId() { return $this->nextSessionId; }
    public function setNextSessionId($nextSessionId) {
        $this->nextSessionId = $nextSessionId;
    }

    /** Internal field.
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CashSession", mappedBy="cashRegister") */ 
    protected $sessions;
    public function getSessions() { return $this->sessions; }
    
    /** Add +1 to the next ticket id and return it.
     * Must be called everytime a ticket is registered to update the counter
     * and check if no ticket was forgotten on the way. */
    public function incrementNextTicketId() {
        $this->setNextTicketId($this->getNextTicketId() + 1);
        return $this->getNextTicketId();
    }

    /** Add +1 to the next session id and return it.
     * Must be called everytime a session is registered to update the counter
     * and check if no session was forgotten on the way. */
    public function incrementNextSessionId() {
        $this->setNextSessionId($this->getNextSessionId() + 1);
        return $this->getNextSessionId();
    }

}
