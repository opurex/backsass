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

use \Pasteque\Server\CommonAPI\VersionAPI;
use \Pasteque\Server\Model\Field\BoolField;
use \Pasteque\Server\Model\Field\DateField;
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * A cash session holds Z Ticket data until it's finalized.
 * Once the cash is closed, the final Z ticket is built.
 * Class CashSession
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="sessions", uniqueConstraints={@UniqueConstraint(name="session_index", columns={"cashregister_id", "sequence"})})
 */
class CashSession extends DoctrineMainModel
{
    /** Close type for a day close. */
    const CLOSE_SIMPLE = 0;
    /** Close type for a period close. */
    const CLOSE_PERIOD = 1;
    /** Close type for a fiscal year close. */
    const CLOSE_FYEAR = 2;

    protected static function getDirectFieldNames() {
        return [
                new IntField('sequence'),
                new BoolField('continuous'),
                new DateField('openDate', ['nullable' => true]),
                new DateField('closeDate', ['nullable' => true]),
                new FloatField('openCash', ['nullable' => true]),
                new FloatField('closeCash', ['nullable' => true]),
                new FloatField('expectedCash', ['nullable' => true]),
                new IntField('ticketCount', ['nullable' => true]),
                new IntField('custCount', ['nullable' => true]),
                new FloatField('cs', ['nullable' => true]),
                new FloatField('csPeriod'),
                new FloatField('csFYear'),
                new FloatField('csPerpetual')
                ];
        // 'closeType' (optional) imported on close but not exported nor stored.
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'cashRegister',
                 'class' => '\Pasteque\Server\Model\CashRegister'
                 ],
                [
                 'name' => 'payments',
                 'class' => '\Pasteque\Server\Model\CashSessionPayment',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' => 'taxes',
                 'class' => '\Pasteque\Server\Model\CashSessionTax',
                 'array' => true,
                 'embedded' => true 
                 ],
                [
                 'name' => 'catSales',
                 'class' => '\Pasteque\Server\Model\CashSessionCat',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' => 'catTaxes',
                 'class' => '\Pasteque\Server\Model\CashSessionCatTax',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' => 'custBalances',
                 'class' => '\Pasteque\Server\Model\CashSessionCustBalance',
                 'array' => true,
                 'embedded' => true
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return ['cashRegister', 'sequence'];
    }
    public function getReference() {
        return ['cashRegister' => $this->getCashRegister()->getId(),
                'sequence' => $this->getSequence];
    }

    public function __construct() {
        $this->taxes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->catSales = new \Doctrine\Common\Collections\ArrayCollection();
        $this->catTaxes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->custBalances = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Internal Id of the session. Required to link taxes and payments.
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Id of a cash register
     * @var int
     * @SWG\Property(format="int32")
     * @ManyToOne(targetEntity="\Pasteque\Server\Model\CashRegister", inversedBy="sessions")
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
     * This is a client-side flag to checks when the cash is opened
     * if the previous cash is still in local cache.
     * It is not when the cache was deleted or when switching machine.
     * This should not happens frequently and is used to check for the
     * "disconnect/delete cache/restart" trick to delete the first tickets
     * silently.
     * @Column(type="boolean")
     */
    protected $continuous = false;
    public function getContinuous() { return $this->continuous; }
    public function isContinuous() { return $this->getContinuous(); }
    public function setContinuous($continuous) {
        $this->continuous = $continuous;
    }

    /**
     * Open date (as a datetime) of session's cash register opening.
     * Read-only. Will throw an exception if trying to override it.
     * @var string|null
     * @SWG\Property(format="date-time")
     * @Column(type="datetime", nullable=true)
     */
    protected $openDate = null;
    public function getOpenDate() { return $this->openDate; }
    /** @throws \UnexpectedValueException When trying to override it. */
    public function setOpenDate($openDate) {
        if ($this->openDate === null) {
            $this->openDate = $openDate;
        } else if (!DateUtils::equals($this->openDate, $openDate)) {
            throw new \UnexpectedValueException('Open date is read only');
        }
    }

    /**
     * Close date (as a datetime) of session's cash register closure
     * Read-only. Will throw an exception if trying to override it.
     * @var string|null
     * @SWG\Property(format="date-time")
     * @Column(type="datetime", nullable=true)
     */
    protected $closeDate = null;
    public function getCloseDate() { return $this->closeDate; }
    /** @throws \UnexpectedValueException When trying to override it. */
    public function setCloseDate($closeDate) {
        if ($this->closeDate === null) {
            $this->closeDate = $closeDate;
        } else if (!DateUtils::equals($this->closeDate,  $closeDate)) {
            throw new \UnexpectedValueException('Close date is read only');
        }
    }

    /**
     * Amount of cash at session's cash register opening
     * Read-only. Will throw an exception if trying to override it.
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float", nullable=true)
     */
    protected $openCash = null;
    public function getOpenCash() {
        if ($this->openCash === null) { return null; }
        else { return round($this->openCash, 5); }
    }
    /** @throws \UnexpectedValueException When trying to override it. */
    public function setOpenCash($openCash) {
        if ($this->openCash === null) {
            $this->openCash = ($openCash === null) ? null : round($openCash, 5);
        } else if (round($this->openCash, 5) != round($openCash, 5)) {
            throw new \UnexpectedValueException('Open cash is read only');
        }
    }

    /**
     * Amount of cash at session's cash register closing
     * Read-only. Will throw an exception if trying to override it.
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float", nullable=true)
     */
    protected $closeCash = null;
    public function getCloseCash() {
        if ($this->closeCash === null) { return null; }
        else { return round($this->closeCash, 5); }
    }
    /** @throws \UnexpectedValueException When trying to override it. */
    public function setCloseCash($closeCash) {
        if ($this->closeCash === null) {
            $this->closeCash = ($closeCash === null) ? null : round($closeCash, 5);
        } else if (round($this->closeCash, 5) != round($closeCash, 5)) {
            throw new \UnexpectedValueException('Close cash is read only');
        }
    }

    /**
     * Diffence's amount of cash at session's cash register closure.
     * Stored in database only for performance.
     * This field must computed automatically and stored on session close.
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float", nullable=true)
     */
    protected $expectedCash = null;
    public function getExpectedCash() {
        if ($this->expectedCash === null) { return null; }
        else { return round($this->expectedCash, 5); }
    }
    public function setExpectedCash($expectedCash) {
        $this->expectedCash = ($expectedCash === null) ? null : round($expectedCash, 5);
    }

    /**
     * Number of the tickets in the session. Updated only for closed cashes.
     * @var int
     * @SWG\Property(format="int32")
     * @Column(type="integer", nullable=true)
     */
    protected $ticketCount = null;
    public function getTicketCount() { return $this->ticketCount; }
    public function setTicketCount($ticketCount) {
        if ($this->ticketCount === null) { $this->ticketCount = $ticketCount; }
        else if ($this->ticketCount != $ticketCount) {
            throw new \UnexpectedValueException('Ticket count is read only');
        }
    }
    /** Private method to remove ticketCount from structs if it is set when not closed. */
    protected function resetTicketCount() { $this->ticketCount = null; }

    /**
     * Number of customers in the session. Updated only for closed cashes.
     * @var int
     * @SWG\Property(format="int32")
     * @Column(type="integer", nullable=true)
     */
    protected $custCount = null;
    public function getCustCount() { return $this->custCount; }
    /** @throws \UnexpectedValueException When trying to override it. */
    public function setCustCount($custCount) {
        if ($this->custCount === null) { $this->custCount = $custCount; }
        else if ($this->custCount != $custCount) {
            throw new \UnexpectedValueException('Customer count is read only');
        }
    }
    /** Private method to remove custCount from structs if it is set when not closed. */
    protected function resetCustCount() { $this->custCount = null; }

    /**
     * Consolidated sales. Read only and only set on close.
     * Read-only. Will throw an exception if trying to override it.
     * @var float
     * @SWG\Property(format="double", nullable=true)
     * @Column(type="float", nullable=true)
     */
    protected $cs = null;
    public function getCs() {
        if ($this->cs === null) { return null; }
        else { return round($this->cs, 5); }
    }
    /** @throws \UnexpectedValueException When trying to override it. */
    public function setCs($cs) {
        if ($this->cs === null) {
            $this->cs = ($cs === null) ? null : round($cs, 5);
        } else if (round($this->cs, 5) != round($cs, 5)) {
            throw new \UnexpectedValueException('Consolidated sales is read only');
        }
    }
    /** Private method to remove CS from structs if it is set when not closed. */
    protected function resetCS() { $this->cs = null; }

    /**
     * Consolidated sales total by period. It is automatically computed
     * on close.
     * @var float
     * @SWG\Property(format="double", nullable=false)
     * @Column(type="float", nullable=false)
     */
    protected $csPeriod = 0.0;
    public function getCsPeriod() {
        return round($this->csPeriod, 5);
    }
    public function setCsPeriod($csPeriod) {
            $this->csPeriod = round($csPeriod, 5);
    }

    /**
     * Consolidated sales total by fiscal year. It is automatically computed
     * on close.
     * @var float
     * @SWG\Property(format="double", nullable=false)
     * @Column(type="float", nullable=false)
     */
    protected $csFYear = 0.0;
    public function getCsFYear() {
        return round($this->csFYear, 5);
    }
    public function setCsFYear($csFYear) {
            $this->csFYear = round($csFYear, 5);
    }

    /**
     * Consolidated sales total. It is never reset.
     * @Column(type="float", nullable= false)
     */
    protected $csPerpetual = 0.0;
    public function getCsPerpetual() {
        return round($this->csPerpetual, 5);
    }
    public function setCsPerpetual($csPerpetual) {
        $this->csPerpetual = round($csPerpetual, 5);
    }

    /**
     * Array of tax totals. It holds the final tax base/amount for each tax.
     * @var \Pasteque\CashSessionTax[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CashSessionTax", mappedBy="cashSession", cascade={"persist"}, orphanRemoval=true)
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
        $tax->setCashSession($this);
    }
    public function removeTax($tax) {
        $this->taxes->removeElement($tax);
        $tax->setCashSession(null);
    }

    /**
     * Array of total amount of payments by mode.
     * @var \Pasteque\CashSessionPayment[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CashSessionPayment", mappedBy="cashSession", cascade={"persist"}, orphanRemoval=true)
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
        $payment->setCashSession($this);
    }
    public function removePayment($payment) {
        $this->payments->removeElement($payment);
        $payment->setCashSession(null);
    }


    /**
     * Array of total amount of cs by category.
     * @var \Pasteque\CashSessionCat[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CashSessionCat", mappedBy="cashSession", cascade={"persist"}, orphanRemoval=true)
     */
    protected $catSales;
    public function getCatSales() { return $this->catSales; }
    public function setCatSales($catSales) {
        $this->catSales->clear();
        foreach ($catSales as $cat) {
            $this->addCatSales($cat);
        }
    }
    public function clearCatSales() {
        $this->getCatSales()->clear();
    }
    public function addCatSales($cat) {
        $this->catSales->add($cat);
        $cat->setCashSession($this);
    }
    public function removeCatSales($cat) {
        $this->catSales->removeElement($tax);
        $cat->setCashSession(null);
    }


    /**
     * Array of tax totals by category.
     * @var \Pasteque\CashSessionCatTax[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CashSessionCatTax", mappedBy="cashSession", cascade={"persist"}, orphanRemoval=true)
     */
    protected $catTaxes;
    public function getCatTaxes() { return $this->catTaxes; }
    public function setCatTaxes($catTaxes) {
        $this->catTaxes->clear();
        foreach ($catTaxes as $catTax) {
            $this->addCatTax($catTax);
        }
    }
    public function clearCatTaxes() {
        $this->getCatTaxes()->clear();
    }
    public function addCatTax($catTax) {
        $this->catTaxes->add($catTax);
        $catTax->setCashSession($this);
    }
    public function removeCatTax($catTax) {
        $this->catTaxes->removeElement($catTax);
        $catTax->setCashSession(null);
    }


    /**
     * Array of total balance change by customer.
     * @var \Pasteque\CashSessionCat[]
     * @SWG\Property()
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CashSessionCustBalance", mappedBy="cashSession", cascade={"persist"}, orphanRemoval=true)
     */
    protected $custBalances;
    public function getCustBalances() { return $this->custBalances; }
    public function setCustBalances($custBalances) {
        $this->custBalances->clear();
        foreach ($custBalances as $custBalance) {
            $this->addCustBalances($custBalance);
        }
    }
    public function clearCustBalances() {
        $this->getCustBalances()->clear();
    }
    public function addCustBalances($custBalance) {
        $this->custBalances->add($custBalance);
        $custBalance->setCashSession($this);
    }
    public function removeCustBalance($custBalance) {
        $this->custBalances->removeElement($custBalance);
        $custBalance->setCashSession(null);
    }


    /** Operation flag for closing. See constants.
     * It is not exported and used only when registering a closed session. */
    private $closeType = CashSession::CLOSE_SIMPLE;
    public function getCloseType() { return $this->closeType; }
    /** Setter used for testing. $closeType is already set within merge. */
    public function setCloseType($closeType) { $this->closeType = $closeType; }

    /**
     * isClosed: return true if closeDate is not null
     * @return bool
     */
    public function isClosed() { return $this->closeDate != null; }

    /**
     * isOpened: return true if openDate is not null
     * @return bool
     */
    public function isOpened() { return $this->openDate != null; }

    /** Initialize the cs sums according to the previous cash session.
     * @param $prevCashSession The session that is closed. It reads $closeType
     * to report and reset the right sums. */
    public function initSums($prevCashSession) {
        if (!$prevCashSession->isClosed()) { return; }
        // Copy taxes sums
        if ($prevCashSession->closeType != static::CLOSE_FYEAR) {
            foreach ($prevCashSession->getTaxes() as $tax) {
                $newTax = new CashSessionTax();
                $newTax->setTax($tax->getTax());
                $newTax->setTaxRate($tax->getTaxRate());
                switch ($prevCashSession->closeType) {
                    case static::CLOSE_SIMPLE: // Keep period
                        $newTax->setBasePeriod($tax->getBasePeriod());
                        $newTax->setAmountPeriod($tax->getAmountPeriod());
                        // nobreak;
                    case static::CLOSE_PERIOD: // Keep fiscal year
                        $newTax->setBaseFYear($tax->getBaseFYear());
                        $newTax->setAmountFYear($tax->getAmountFYear());
                }
                $this->addTax($newTax);
            }
        }
        // Copy CS sums
        $this->setCSPerpetual($prevCashSession->getCSPerpetual());
        switch ($prevCashSession->closeType) {
            case static::CLOSE_SIMPLE:
                // Keep period
                $this->setCSPeriod($prevCashSession->getCSPeriod());
                // nobreak
            case static::CLOSE_PERIOD:
                // Keep fiscal year
                $this->setCSFYear($prevCashSession->getCSFYear());
                // nobreak
            case static::CLOSE_FYEAR:
                // Keep nothing
                break;
        }
    }

    public function merge($struct, $dao) {
        // Prevent updating sums when the session is not closed.
        $structTaxes = null;
        if (empty($struct['closeDate'])) {
            unset($struct['csPeriod']);
            unset($struct['csFYear']);
            unset($struct['csPerpetual']);
            unset($struct['taxes']);
        } else {
            // Save and unset taxes to merge data manually
            $structTaxes = $struct['taxes'];
            unset($struct['taxes']);
        }
        parent::merge($struct, $dao);
        // Compute csPerpetual if it was not given (by older clients)
        if (empty($struct['csPerpetual']) && !empty($struct['closeDate'])) {
            if ($this->sequence == 1) {
                $this->setCSPerpetual($this->getCS());
            } else {
                $prevSearch = $dao->search(static::class,
                        [new DAOCondition('cashRegister', '=',
                                $this->getCashRegister()),
                        new DAOCondition('sequence', '=',
                                $this->getSequence() - 1)]);
                $previousSession = $prevSearch[0];
                $this->setCSPerpetual($previousSession->getCSPerpetual()
                        + $this->getCS());
            }
        }
        // Merge taxes
        if ($structTaxes !== null && !empty($structTaxes)) {
            foreach ($structTaxes as $sTax) {
                $merged = false;
                foreach ($this->getTaxes() as $mTax) {
                    if ($sTax['tax'] == $mTax->getTax()->getId()) {
                        $mTax->merge($sTax, $dao);
                        $merged = true;
                    }
                }
                if (!$merged) {
                    $tax = CashSessionTax::loadOrCreate($sTax, $this, $dao);
                    $tax->merge($sTax, $dao);
                    $this->addTax($tax);
                }
            }
        }
        // Load closeType if set
        if (!empty($struct['closeType'])) {
            $this->closeType = $struct['closeType'];
        }
    }

    public function toStruct() {
        $struct = parent::toStruct();
        $struct['openDate'] = DateUtils::toTimestamp($this->getOpenDate());
        $struct['closeDate'] = DateUtils::toTimestamp($this->getCloseDate());
        return $struct;
    }

    /** Create struct of the full data to be written in stone
     * (that is, FiscalTicket). The cash session must be closed. */
    public function toStone() {
        $struct = $this->toStruct();
        // Include source version in case it has to be parsed.
        $struct['version'] = VersionAPI::VERSION;
        // Format date in human-readable format
        if ($struct['openDate'] !== null) {
            $struct['openDate'] = $this->getOpenDate()->format('Y-m-d H:i:s');
        }
        if ($struct['closeDate'] !== null) {
            $struct['closeDate'] = $this->getCloseDate()->format('Y-m-d H:i:s');
        }
        // Fetch associative fields and include the data.
        unset($struct['id']);
        $struct['cashRegister'] = ['reference' => $this->getCashRegister()->getReference(),
                'label' => $this->getCashRegister()->getLabel()];
        for ($i = 0; $i < count($struct['payments']); $i++) {
            $payment = $struct['payments'][$i];
            unset($payment['id']);
            unset($payment['cashSession']);
            $paymentMode = $this->getPayments()->get($i)->getPaymentMode();
            $currency = $this->getPayments()->get($i)->getCurrency();
            $payment['paymentMode'] = ['reference' =>$paymentMode->getReference(),
                    'label' => $paymentMode->getLabel()];
            $payment['currency'] = ['reference' => $currency->getReference(),
                    'label' => $currency->getLabel()];
            $struct['payments'][$i] = $payment;
        }
        for ($i = 0; $i < count($struct['taxes']); $i++) {
            $tax = $struct['taxes'][$i];
            unset($tax['id']);
            unset($tax['cashSession']);
            unset($tax['sequence']);
            unset($tax['tax']);
            $struct['taxes'][$i] = $tax;
        }
        for ($i = 0; $i < count($struct['custBalances']); $i++) {
            $bal = $struct['custBalances'][$i];
            unset($bal['id']);
            unset($bal['cashSession']);
            unset($bal['sequence']);
            $c = $this->getCustBalances()->get($i)->getCustomer();
            $bal['customer'] = ['dispName' => $c->getDispName(),
                    'firstName' => $c->getFirstName(),
                    'lastName' => $c->getLastName()];
            $struct['custBalances'][$i] = $bal;
        }
        for ($i = 0; $i < count($struct['catSales']); $i++) {
            $cat = $struct['catSales'][$i];
            unset($cat['id']);
            unset($cat['cashSession']);
            $struct['catSales'][$i] = $cat;
        }
        for ($i = 0; $i < count($struct['catTaxes']); $i++) {
            $catTax = $struct['catTaxes'][$i];
            $tax = $this->getCatTaxes()->get($i)->getTax();
            unset($catTax['id']);
            unset($catTax['cashSession']);
            $catTax['tax'] = $tax->getLabel();
            $catTax['taxRate'] = $tax->getRate();
            $struct['catTaxes'][$i] = $catTax;
        }
        return $struct;
    }
}
