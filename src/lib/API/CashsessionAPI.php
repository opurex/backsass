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

use \Pasteque\Server\API\FiscalAPI;
use \Pasteque\Server\Exception\PastequeException;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\GenericModel;
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for Role. */
class CashsessionAPI extends APIHelper implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\CashSession';

    /** Get an existing cash session or get the last one.
     * @param $id Session Id: [<cash register id>, <sequence>].
     * If sequence is omitted, will get the last one.
     * @return The requested session, created if there aren't any yet. */
    public function get($id) {
        if (!is_array($id)) { $id = array('cashRegister' => $id, 'sequence' =>null); }
        // Look for the cash register
        $cashRegister = $this->dao->read(CashRegister::class, $id['cashRegister']);
        if ($cashRegister == null) {
            return null;
        }
        // Look for session
        if ($id['sequence'] === null) {
            // Look for the latest session for the requested cash register
            $sessions = $this->dao->search(static::MODEL_NAME,
                    [new DAOCondition('cashRegister', '=', $cashRegister)],
                    1, 0, '-sequence');
            if (count($sessions) > 0) {
                return $sessions[0];
            } else {
                // No session for this cash register yet, create it.
                $session = new CashSession();
                $session->setCashRegister($cashRegister);
                $session->setSequence(1);
                $this->dao->write($session);
                $this->dao->commit();
                return $session;
            }
        } else {
            $search = $this->dao->search(static::MODEL_NAME,
                    [new DAOCondition('cashRegister', '=', $cashRegister),
                     new DAOCondition('sequence', '=', $id['sequence'])],
                    1);
            if (count($search) > 0) {
                return $search[0];
            }
            return null;
        }
    }

    /**
     * Register an unexpected error while trying to write a cash session
     * before the session object is readable.
     * @param $input The faulty data.
     * @param $reason The error reason. String message or PastequException.
     */
    public function registerGeneralInputFailure($input, $reason) {
        $sequence = FiscalTicket::getGeneralFailureSequence();
        $fiscalAPI = new FiscalAPI($this->dao);
        $previousFTicket = $fiscalAPI->getLastFiscalTicket(FiscalTicket::TYPE_ZTICKET, $sequence);
        // Create the failure ticket
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setType(FiscalTicket::TYPE_ZTICKET);
        $fiscalTicket->setSequence($sequence);
        if ($previousFTicket !== null) {
            $fiscalTicket->setNumber($previousFTicket->getNumber() + 1);
        } else {
            $fiscalTicket->setNumber(1);
        }
        $fiscalTicket->setDate(new \DateTime());
        $stone = ['input' => $input, 'failure' => $reason];
        if ($reason instanceof PastequeException) {
            $stone['failure'] = $reason->toStruct();
        }
        $fiscalTicket->setContent(json_encode($stone));
        // Sign
        $fiscalTicket->sign($previousFTicket);
        // Write
        $this->dao->write($fiscalTicket);
        $this->updateEOSTicket($fiscalTicket);
        $this->dao->commit();
    }

    /** Write a FiscalTicket about the failed zticket, so that there is a trace
     * and the ticket can be purged client-side. It does commit.
     * @param $session The faulty session.
     * @param $reason The technical fault, added in the FiscalTicket. */
    private function registerFailure($session, $reason) {
        $sequence = FiscalTicket::getFailureZTicketSequence($session);
        $fiscalAPI = new FiscalAPI($this->dao);
        $previousFTicket = $fiscalAPI->getLastFiscalTicket(FiscalTicket::TYPE_ZTICKET, $sequence);
        // Create the failure ticket
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setType(FiscalTicket::TYPE_ZTICKET);
        $fiscalTicket->setSequence($sequence);
        if ($previousFTicket !== null) {
            $fiscalTicket->setNumber($previousFTicket->getNumber() + 1);
        } else {
            $fiscalTicket->setNumber(1);
        }
        $fiscalTicket->setDate(new \DateTime());
        $stone = $session->toStone();
        $stone['failure'] = $reason;
        $fiscalTicket->setContent(json_encode($stone));
        // Sign
        $fiscalTicket->sign($previousFTicket);
        // Write
        $this->dao->write($fiscalTicket);
        $this->updateEOSTicket($fiscalTicket);
        $this->dao->commit();
    }

    /** Write a cash session. At least two calls must be performed during the lifespan.
     * One to open, one to close. Tickets are stored between these calls.
     * When closing a session, the next one is automatically created.
     * @param $session The session to register (cannot be an array of sessions).
     * @return The session after registration.
     * @throws \BadMethodCallException When trying to close a session without
     * writing it as opened before, or when trying to update an already closed
     * session.
     * @throws \Exception When an unknown error occurs.
     * Nothing is registered in that case (at least it shouldn't). */
    public function write($session) {
        if (get_class($session) !== static::MODEL_NAME) {
            throw new \InvalidArgumentException(sprintf('Incompatible class %s expecting %s', get_class($session), static::MODEL_NAME));
        }
        // Defensive checks
        // Reserved number 0
        if ($session->getSequence() === 0) {
            $rejectReason = 'Session number 0 is reserved.';
            $this->registerFailure($session, $rejectReason);
            throw new \BadMethodCallException($rejectReason);
        }
        $currentData = $this->dao->readSnapshot(static::MODEL_NAME, $session->getId());
        // Overwriting a closed session
        if ($currentData !== null && $currentData->isClosed()) {
            if ($currentData->equals($session)) {
                // Nothing new, consider it is ok
                return $currentData;
            }
            $rejectReason = 'Closed sessions are read-only.';
            $this->registerFailure($session, $rejectReason);
            throw new \BadMethodCallException($rejectReason);
        }
        if ($currentData === null && $session->isClosed()) {
            // Trying to register everything at once, this will prevent
            // registering the tickets. Throw an error.
            $rejectReason = 'Trying to close a session that was not registered as open.';
            $this->registerFailure($session, $rejectReason);
            throw new \BadMethodCallException($rejectReason);
        }
        if ($session->isClosed()) {
            $fiscalAPI = new FiscalAPI($this->dao);
            $sequence = FiscalTicket::getZTicketSequence($session);
            $previousFTicket = $fiscalAPI->getLastFiscalTicket(FiscalTicket::TYPE_ZTICKET, $sequence);
            // Create associated fiscal ticket
            $fiscalTicket = new FiscalTicket();
            $fiscalTicket->setType(FiscalTicket::TYPE_ZTICKET);
            $fiscalTicket->setSequence($sequence);
            if ($previousFTicket !== null) {
                $fiscalTicket->setNumber($previousFTicket->getNumber() + 1);
            } else {
                $fiscalTicket->setNumber(1);
            }
            $fiscalTicket->setDate(new \DateTime());
            $fiscalTicket->setContent(json_encode($session->toStone()));
            // Sign
            $fiscalTicket->sign($previousFTicket);
            // Write
            $this->dao->write($session);
            $this->dao->write($fiscalTicket);
            $this->updateEOSTicket($fiscalTicket);
            // Create next empty session
            $next = new CashSession();
            $next->setCashRegister($session->getCashRegister());
            $next->setSequence($session->getSequence() + 1);
            $next->setContinuous(true); // until proven different by clients
            $next->initSums($session);
            $this->dao->write($next);
        } else {
            $this->dao->write($session);
        }
        // Commit
        try {
            $this->dao->commit();
        } catch (\Exception $e) {
            // Consider the write error as an input error, try to register
            // a failure ticket
            $rejectReason = 'Error: ' . $e->getMessage();
            $this->registerFailure($session, $rejectReason);
            throw new \BadMethodCallException($rejectReason);
            // If an error occurs while registering the failure, the exception
            // will be propagated.
        }
        return $session;
    }

    /** Update the signature of the end-of-sequence FiscalTicket with
     * the last Z ticket inserted. Does not commit. */
    protected function updateEOSTicket($lastFTicket) {
        if ($lastFTicket === null) {
            // TODO: warning
            return;
        }
        $eosTicket = $this->dao->read('\Pasteque\Server\Model\FiscalTicket',
                ['type' => FiscalTicket::TYPE_ZTICKET,
                'sequence' => $lastFTicket->getSequence(),
                'number' => 0]);
        if ($eosTicket === null) {
            $eosTicket = new FiscalTicket();
            $eosTicket->setType(FiscalTicket::TYPE_ZTICKET);
            $eosTicket->setSequence($lastFTicket->getSequence());
            $eosTicket->setNumber(0);
            $eosTicket->setContent('EOS');
        }
        $eosTicket->setDate($lastFTicket->getDate());
        $eosTicket->sign($lastFTicket);
        $this->dao->write($eosTicket);
    }

    /** Get the summary of a session from registered tickets.
     * This is not the Z Ticket, but it looks like it. */
    public function summary($cashSession) {
        // This one is used by Desktop to show the summary before closing
        // the cash session.
        $ret = new GenericModel();
        // Initialize result
        $ret->set('cashRegister', $cashSession->getCashRegister()->getId());
        $ret->set('sequence', $cashSession->getSequence());
        $ticketCount = 0;
        $custCount = null;
        $paymentCount = 0;
        $cs = 0.0;
        $payments = [];
        $custBalances = [];
        $taxes = [];
        $catSales = [];
        $catTaxes = [];
        // Load tickets and add them to the summary
        $tickets = $this->dao->search(Ticket::class,
                [new DAOCondition('cashRegister', '=', $cashSession->getCashRegister()),
                 new DAOCondition('sequence', '=', $cashSession->getSequence())]);
        $summaryPmts = [];
        $summaryTaxes = [];
        $summaryCats = []; // Meow?
        $summaryCatTaxes = [];
        $summaryCusts = [];
        foreach ($tickets as $tkt) {
            $ticketCount++;
            $cs += $tkt->getFinalPrice();
            if (!empty($tkt->getCustCount())) {
                if ($custCount === null) {
                    $custCount = $tkt->getCustCount();
                } else {
                    $custCount += $tkt->getCustCount();
                }
            }
            $tktPayments = $tkt->getPayments();
            // Payments sums
            foreach ($tktPayments as $pmt) {
                $paymentCount++;
                // Because it is for Desktop, it follows the desktop strucure
                // of ZTicket.Payment instead of the one from TicketPayment
                $pmtRef = $pmt->getPaymentMode()->getReference() . $pmt->getCurrency()->getId();
                if (!isset($summaryPmts[$pmtRef])) {
                    $summaryPmts[$pmtRef] = ['type' => $pmt->getPaymentMode()->getReference(),
                            'amount' => 0.0,
                            'currency' => $pmt->getCurrency()->getId(),
                            'currencyAmount' => 0.0];
                }
                $summaryPmts[$pmtRef]['amount'] += $pmt->getAmount();
                $summaryPmts[$pmtRef]['currencyAmount'] += $pmt->getCurrencyAmount();
            }
            // Cust balance sums
            if ($tkt->getCustomer() != null && abs($tkt->getCustBalance()) > 0.005) {
                $custId = $tkt->getCustomer()->getId();
                if (!isset($summaryCusts[$custId]) && $tkt->getCustBalance()) {
                    $summaryCusts[$custId] = ['customer' => $custId,
                            'balance' => 0.0];
                }
                $summaryCusts[$custId]['balance'] += $tkt->getCustBalance();
            }
            // Tax sums
            $tktTax = $tkt->getTaxes();
            foreach ($tktTax as $tax) {
                $taxId = $tax->getTax()->getId();
                if (!isset($summaryTaxes[$taxId])) {
                    $summaryTaxes[$taxId] = ['tax' => $taxId, // desktop
                            'base' => 0.0,
                            'amount' => 0.0];
                }
                $summaryTaxes[$taxId]['base'] += $tax->getBase();
                $summaryTaxes[$taxId]['amount'] += $tax->getAmount();
            }
            // Line sums
            $tktLines = $tkt->getLines();
            foreach ($tktLines as $line) {
                if ($line->getProduct() !== null
                        && ($line->getProduct()->isPrepay())) {
                    // Remove from CS if it is a prepayment refill
                    if ($line->getFinalTaxedPrice() !== null) {
                        $cs -= $line->getFinalTaxedPrice();
                    } else {
                        $cs -= $line->getFinalPrice(); // it should be the same
                    }
                    // And don't include it in category sales and taxes
                    // It is already set in customer balances
                    continue;
                }
                // Category
                // This one may give rounding issues. The accounting service
                // will deal with it.
                $catId = ($line->getProduct() != null)
                        ? $line->getProduct()->getCategory()->getId()
                        : 0;
                if (!isset($summaryCats[$catId])) {
                    $summaryCats[$catId] = ['category' => $catId, // desktop
                            'amount' => 0.0];
                }
                $finalPrice = $line->getFinalPrice();
                if ($finalPrice === null) {
                    // This is where rounding issues come.
                    $finalPrice = $line->getFinalTaxedPrice()
                            / (1.0 + $line->getTax()->getRate());
                    $finalPrice = round($finalPrice, 2);
                }
                $summaryCats[$catId]['amount'] += $finalPrice;
                // Category tax
                if ($line->getTax() === null) {
                    continue;
                }
                $taxId = $line->getTax()->getId();
                if (!isset($summaryCatTaxes[$catId])) {
                    $summaryCatTaxes[$catId] = [];
                }
                if (!isset($summaryCatTaxes[$catId][$taxId])) {
                    $ref = ""; $label = ""; // for no category
                    if ($catId != 0) {
                        $category = $line->getProduct()->getCategory();
                        $ref = $category->getReference();
                        $label = $category->getLabel();
                    }
                    $summaryCatTaxes[$catId][$taxId] = ['tax' => $taxId,
                            'reference' => $ref, 'label' => $label,
                            'base' => 0.0, 'amount' => 0.0];
                }
                $summaryCatTaxes[$catId][$taxId]['base'] += $finalPrice;
                $taxAmount = round($finalPrice * $line->getTax()->getRate(), 2);
                $summaryCatTaxes[$catId][$taxId]['amount'] += $taxAmount;
            }
        } // foreach $tickets end
        foreach ($summaryPmts as $pmtRef => $sum) {
            $pmtSum = new GenericModel();
            foreach ($sum as $key => $value) {
                $pmtSum->set($key, $value);
            }
            $payments[] = $pmtSum;
        }
        foreach ($summaryCusts as $custId => $sum) {
            $custSum = new GenericModel();
            foreach ($sum as $key => $value) {
                $custSum->set($key, $value);
            }
            $custBalances[] = $custSum;
        }
        foreach ($summaryTaxes as $taxId => $sum) {
            $taxSum = new GenericModel();
            foreach ($sum as $key => $value) {
                $taxSum->set($key, $value);
            }
            $taxes[] = $taxSum;
        }
        foreach ($summaryCats as $catId => $sum) {
            $catSum = new GenericModel();
            foreach ($sum as $key => $value) {
                $catSum->set($key, $value);
            }
            $catSales[] = $catSum;
        }
        foreach ($summaryCatTaxes as $catId => $tax) {
            foreach ($tax as $taxId => $sum) {
                $catTaxSum = new GenericModel();
                foreach ($sum as $key => $value) {
                    $catTaxSum->set($key, $value);
                }
                $catTaxes[] = $catTaxSum;
            }
        }
        $ret->set('ticketCount', $ticketCount);
        $ret->set('custCount', $custCount);
        $ret->set('paymentCount', $paymentCount);
        $ret->set('cs', $cs);
        $ret->set('payments', $payments);
        $ret->set('custBalances', $custBalances);
        $ret->set('taxes', $taxes);
        $ret->set('catSales', $catSales);
        $ret->set('catTaxes', $catTaxes);
        return $ret;
    }

    /** Delete is disabled. */
    public function delete($id) {}
}
