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

use \Pasteque\Server\Model\Field\DateField;
use \Pasteque\Server\Model\Field\EnumField;
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Read-only tickets. This class definition cannot be updated in the future
 * as the database records must be kept unedited no matter what.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="fiscaltickets")
 */
class FiscalTicket extends DoctrineMainModel
{
    const TYPE_ZTICKET = 'z';
    const TYPE_TICKET = 'tkt';

    protected static function getDirectFieldNames() {
        return [
                new StringField('type'), // allow various kind of tickets
                new StringField('sequence'),
                new IntField('number'),
                new DateField('date'),
                new StringField('content', ['length' => null]),
                new StringField('signature')];
    }
    protected static function getAssociationFields() {
        return []; // Must be empty, FiscalTickets must be self-contained.
    }
    protected static function getReferenceKey() {
        return 'id';
    }
    /** FiscalTickets don't have reference yet. This is the same as getId(). */
    public function getReference() {
        return $this->getId();
    }

    public function getId() {
        return ['type' => $this->getType(),
                'sequence' => $this->getSequence(),
                'number' => $this->getNumber()];
    }
    
    /**
     * Type of the ticket
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     * @Id
     */
    protected $type;
    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; }

    /**
     * Name of the sequence, in which the number is incremental.
     * Most of the time the cash register reference.
     * This is a string and not an int for flexibility.
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     * @Id
     */
    protected $sequence;
    public function getSequence() { return $this->sequence; }
    public function setSequence($sequence) {
        $this->sequence = $sequence;
    }

    /**
     * Ticket number. Incremental inside a given sequence.
     * For a Z ticket it is the number of the cash session, for a ticket
     * the number of the ticket.
     * @var int
     * @SWG\Property()
     * @Column(type="integer")
     * Cannot use null as number for EOS ticket, because Doctrine doesn't
     * support it (id with a null value)
     * @Id
     */
    protected $number;
    public function getNumber() { return $this->number; }
    public function setNumber($number) { $this->number = $number; }

    /**
     * Ticket date. It is the payment date for tickets, and close date for z.
     * @var date
     * @SWG\Property()
     * @Column(type="datetime")
     */
    protected $date;
    public function getDate() { return $this->date; }
    public function setDate($date) { $this->date = $date; }

    /**
     * Ticket data.
     * @var string|null
     * @SWG\Property()
     * @Column(type="text")
     */
    protected $content;
    public function getContent() { return $this->content; }
    public function setContent($content) { $this->content = $content; }

    /**
     * Ticket signature. The signature is computed from ticket n and n-1.
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $signature;
    public function getSignature() { return $this->signature; }
    public function setSignature($signature) {
        if ($this->signature !== null && $this->number !== 0) {
            throw new \InvalidArgumentException('Changing the signature is not allowed.');
        }
        $this->signature = $signature;
    }

    protected function getHashBase() {
        return $this->getSequence() . '-' . $this->getNumber()
                . '-' . DateUtils::toTimestamp($this->getDate())
                . '-' . $this->getContent();
    }

    /**
     * Set the signature of the ticket.
     * @param $prevTicket The previous FiscalTicket, may be null
     * for the first one.
     * @param $algo Name of algorithm to use for hashes. It is used internally
     * to check signatures and retro-compatibility tests. Default is sha3-512.
     */
    public function sign($prevTicket, $algo = 'sha3-512') {
        $data = null;
        if ($prevTicket === null) {
            $data = $this->getHashBase();
        } else {
            $data = $prevTicket->getSignature() . '-' . $this->getHashBase();
        }
        if ($algo == 'bcrypt') {
            // Old bcrypt signature. Do not use it, only there for debug
            // and retro-compatibility checks
            $signature = password_hash($data, PASSWORD_DEFAULT);
            if ($signature === false) {
                // Error
            }
            $this->setSignature($signature);
            return;
        }
        $signature = hash($algo, $data);
        if ($signature === false) {
            // Error
        }
        $this->setSignature(sprintf('%s:%s', $algo, $signature));
    }

    /** Check the signature of the ticket. */
    public function checkSignature($prevTicket) {
        $data = null;
        if ($prevTicket === null) {
            $data = $this->getHashBase();
        } else {
            $data = $prevTicket->getSignature() . '-' . $this->getHashBase();
        }
        $sign = $this->getSignature();
        if (substr($sign, 0, 2) == '$2') {
            // Old bcrypt signature
            return password_verify($data, $this->getSignature());
        }
        $i = strpos($sign, ':');
        if ($i === false) {
            // Unknown algorithm
            return false;
        }
        $alg = substr($sign, 0, $i);
        $hash = hash($alg, $data);
        return $sign == sprintf('%s:%s', $alg, $hash);
    }

    /** Get the sequence id as string for FiscalTicket. */
    public static function getTicketSequence($ticket) {
        // Pad with 0 to be able to sort by value asc or desc
        return sprintf('%010d', $ticket->getCashRegister()->getId());
    }
    public static function getFailureTicketSequence($ticket) {
        return sprintf('failure-%s', static::getTicketSequence($ticket));
    }

    public static function getZTicketSequence($cashSession) {
        // Pad with 0 to be able to sort by value asc or desc
        return sprintf('%010d', $cashSession->getCashRegister()->getId());
    }
    public static function getFailureZTicketSequence($cashSession) {
        return sprintf('failure-%s', static::getZTicketSequence($cashSession));
    }

    /**
     * Get the sequence for general errors before a regular FiscalTicket
     * could be produced.
     */
    public static function getGeneralFailureSequence() {
        return 'failure-general';
    }

    public function toStruct() {
        $struct = parent::toStruct();
        $struct['date'] = DateUtils::toTimestamp($this->getDate());
        return $struct;
    }

}
