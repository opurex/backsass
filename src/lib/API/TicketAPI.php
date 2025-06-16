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

namespace Pasteque\Server\API;

use \Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use \Pasteque\Server\Exception\InvalidRecordException;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\PastequeException;
use \Pasteque\Server\Exception\UnicityException;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for Role. */
class TicketAPI extends APIHelper implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\Ticket';
    const DEFAULT_ORDER = '-number';

    /**
     * Regiser a fiscal ticket in the database and commit.
     * For concurrential access, the number will be updated until
     * the ticket is written.
     * @param $type The type of the fiscal ticket
     * @param $sequence The sequence of the fiscal ticket
     * @param $content The content, as an associative array
     * @return The fiscal ticket
     */
    private function registerFiscal($type, $sequence, $content) {
        // Warning: function copied in TicketAPITest and not called directly
        $fAPI = new FiscalAPI($this->dao);
        $registered = false;
        while (!$registered) {
            $fTicket = new FiscalTicket();
            $prevFTicket = $fAPI->getLastFiscalTicket($type, $sequence);
            if ($prevFTicket !== null) {
                $fTicket->setNumber($prevFTicket->getNumber() + 1);
            } else {
                $fTicket->setNumber(1);
            }
            $fTicket->setType($type);
            $fTicket->setSequence($sequence);
            $fTicket->setDate(new \DateTime());
            $fTicket->setContent(json_encode($content));
            $fTicket->sign($prevFTicket);
            $this->dao->write($fTicket);
            $this->updateEOSTicket($fTicket);
            try {
                $this->dao->commit();
                $registered = true;
            } catch (UniqueConstraintViolationException $e) {
                // Concurrential DB access, retry
            }
        }
        return $fTicket;
    }

    /**
     * Register an unexpected error while trying to write a ticket
     * before the ticket object is readable.
     * @param $input The faulty data.
     * @param $reason The error reason. String message or PastequException.
     */
    public function registerGeneralInputFailure($input, $reason) {
        $stone = ['input' => $input, 'failure' => $reason];
        if ($reason instanceof PastequeException) {
            $stone['failure'] = $reason->toStruct();
        }
        $this->registerFiscal(FiscalTicket::TYPE_TICKET,
                FiscalTicket::getGeneralFailureSequence(),
                $stone);
    }

    /** Write a FiscalTicket about the failed ticket, so that there is a trace
     * and the ticket can be purged client-side. It does commit.
     * @param $ticket The faulty ticket.
     * @param $reason The technical fault, added in the FiscalTicket. */
    private function registerFailure($ticket, $reason) {
        $stone = $ticket->toStone();
        $stone['failure'] = $reason;
        $this->registerFiscal(FiscalTicket::TYPE_TICKET,
                FiscalTicket::getFailureTicketSequence($ticket),
                $stone);
    }

    /**
     * Write a ticket and it's associated fiscal ticket. It cannot write
     * multiple tickets at once.
     * @param $ticket The ticket to register (cannot be an array of tickets).
     * @return The ticket after registration.
     * @throws \Pasteque\Exception\InvalidRecordException when trying to
     * update an existing ticket (CSTR_READ_ONLY) or writing number 0
     * (CSTR_GENERATED). A failure fiscal ticket is still registered.
     * @throws \Pasteque\Exception\InvalidFieldException when trying to
     * associate a ticket to a non-opened cash session.
     * @throws \BadMethodCall when an sql error occurs while writing the
     * ticket. A failure ticket is still registered.
     * @throws \Exception When an unknown error occurs while registering the
     * failure ticket.
     */
    public function write($ticket) {
        if (get_class($ticket) != static::MODEL_NAME) {
            throw new \InvalidArgumentException(sprintf('Incompatible class %s expecting %s', get_class($ticket), static::MODEL_NAME));
        }
        // Defensive checks
        // Reserved number 0
        if ($ticket->getNumber() === 0) {
            $rejectReason = 'Ticket number 0 is reserved.';
            $this->registerFailure($ticket, $rejectReason);
            throw new InvalidRecordException(InvalidRecordException::CSTR_GENERATED,
                    static::MODEL_NAME, $ticket->getDictId());
        }
        // Check for an existing ticket
        $search = $this->dao->search(static::MODEL_NAME,
                [new DAOCondition('cashRegister', '=', $ticket->getCashRegister()),
                 new DAOCondition('number', '=', $ticket->getNumber())]);
        if (count($search) > 0) {
            $oldTkt = $this->dao->readSnapshot(static::MODEL_NAME,
                    $search[0]->getId());
            if ($oldTkt->equals($ticket)) {
                // Nothing new, consider it is ok
                return $oldTkt;
            } else {
                // Overriding an existing ticket
                $rejectReason = 'Tickets are read only.';
                $this->registerFailure($ticket, $rejectReason);
                throw new InvalidRecordException(
                        InvalidRecordException::CSTR_READ_ONLY,
                        static::MODEL_NAME, $ticket->getDictId());
           }
        }
        // Cash session must be opened
        $sessSearch = $this->dao->search(CashSession::class,
                [new DAOCondition('cashRegister', '=', $ticket->getCashRegister()),
                 new DAOCondition('sequence', '=', $ticket->getSequence())], 1);
        $session = null;
        if (count($sessSearch) > 0) {
            $session = $sessSearch[0];
        }
        if ($session === null) {
            $rejectReason = 'Cash session not found.';
            $this->registerFailure($ticket, $rejectReason);
            $crDictId = $ticket->getDictId();
            unset($crDictId['number']);
            throw new InvalidFieldException(InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                    static::MODEL_NAME, 'cashRegister&sequence',
                    $ticket->getDictId(), $crDictId);
        } elseif ($session->isClosed() || !$session->isOpened()){
            $rejectReason = 'Tickets must be assigned to an opened cash session.';
            $this->registerFailure($ticket, $rejectReason);
            $crDictId = $ticket->getDictId();
            unset($crDictId['number']);
            throw new InvalidFieldException(InvalidFieldException::CSTR_OPENED_CASH,
                    static::MODEL_NAME, 'cashRegister&sequence',
                    $ticket->getDictId(), $crDictId);
        }
        // Write
        $this->dao->write($ticket);
        // Check prepayment refill, use and debt, update customer.
        if ($ticket->getCustomer() !== null) {
            $customer = $ticket->getCustomer();
            $balance = $ticket->getCustBalance();
            if ($balance > 0.005 || $balance < -0.005) {
                $customer->addBalance($balance);
                $this->dao->write($customer);
            }
        }
        // Commit
        try {
            $this->dao->commit();
        } catch (\Exception $e) {
            // Maybe it is because of duplicated ticket lines
            $lineDispOrders = [];
            foreach ($ticket->getLines() as $line) {
                $dispOrder = $line->getDispOrder();
                if (array_key_exists($dispOrder, $lineDispOrders)) {
                    $rejectReason = sprintf('Error: duplicated line n°%d',
                            $dispOrder);
                    $this->registerFailure($ticket, $rejectReason);
                    throw new UnicityException(Ticket::class,
                            'lines.dispOrder', $dispOrder);
                } else {
                    $lineDispOrders[$dispOrder] = true;
                }
            }
            // Or duplicated tax lines
            $taxes = [];
            foreach ($ticket->getTaxes() as $tax) {
                $taxId = $tax->getTax()->getId();
                if (array_key_exists($taxId, $taxes)) {
                    $rejectReason = sprintf('Error: duplicated tax with id %d',
                            $taxId);
                    $this->registerFailure($ticket, $rejectReason);
                    throw new UnicityException(Ticket::class,
                            'taxes.tax', $taxId);
                } else {
                    $taxes[$taxId] = true;
                }
            }
            // Anyway, consider the write error as an input error,
            // try to register a failure ticket.
            $rejectReason = 'Error: ' . $e->getMessage();
            $this->registerFailure($ticket, $rejectReason);
            throw new \BadMethodCallException($rejectReason, 0, $e);
            // If an error occurs while registering the failure, the exception
            // will be propagated.
        }
        // Ticket was correctly registered, write fiscal ticket
        // in a new transaction because it will be repeated until it works
        $this->registerFiscal(FiscalTicket::TYPE_TICKET,
                FiscalTicket::getTicketSequence($ticket),
                $ticket->toStone());
        // Update nextTicketId (after commit because it requires the ticket
        // to be registered).
        $cashReg = $ticket->getCashRegister();
        $search = $this->dao->search(Ticket::class,
                new DAOCondition('cashRegister', '=', $cashReg),
                1, 0, '-number');
        if (count($search) > 0) {
            $max = $search[0]->getNumber();
            $cashReg->setNextTicketId($max + 1);
            $this->dao->write($cashReg);
        }
        $this->dao->commit(); // Here there may be an exception thrown.
                              // But the ticket is already registered...
        return $ticket;
    }

    /**
     * Update the signature of the end-of-sequence FiscalTicket with
     * the last ticket inserted. Does not commit.
     * @param \Pasteque\Server\Model\FiscalTicket $lastFTicket The last ticket
     * of the sequence.
     */
    protected function updateEOSTicket($lastFTicket) {
        if ($lastFTicket === null) {
            // TODO: warning
            return;
        }
        $eosTicket = $this->dao->read('\Pasteque\Server\Model\FiscalTicket',
                ['type' => FiscalTicket::TYPE_TICKET,
                'sequence' => $lastFTicket->getSequence(),
                'number' => 0]);
        if ($eosTicket === null) {
            $eosTicket = new FiscalTicket();
            $eosTicket->setType(FiscalTicket::TYPE_TICKET);
            $eosTicket->setSequence($lastFTicket->getSequence());
            $eosTicket->setNumber(0);
            $eosTicket->setContent('EOS');
        }
        $eosTicket->setDate($lastFTicket->getDate());
        $eosTicket->sign($lastFTicket);
        $this->dao->write($eosTicket);
    }

    /**
     * Delete is disabled. Does nothing and return null.
     * @param $id unused.
     * @return null.
     */
    public function delete($id) { return null; }

}
