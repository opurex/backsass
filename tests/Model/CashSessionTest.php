<?php
//    Pasteque server testing
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.
namespace Pasteque\Server;

use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\CashSessionCat;
use \Pasteque\Server\Model\CashSessionCustBalance;
use \Pasteque\Server\Model\CashSessionPayment;
use \Pasteque\Server\Model\CashSessionTax;
use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

/** Test for CashSession and its subclasses CashSessionCat, CashSessionTax... */
class CashSessionTest extends TestCase
{
    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->cashRegister = new CashRegister();
        $this->cashRegister->setReference('cash');
        $this->cashRegister->setLabel('Cash');
        $this->dao->write($this->cashRegister);
        $this->tax = new Tax();
        $this->tax->setLabel('tax');
        $this->tax->setRate(0.1);
        $this->tax2 = new Tax();
        $this->tax2->setLabel('tax2');
        $this->tax2->setRate(0.2);
        $this->dao->write($this->tax);
        $this->dao->write($this->tax2);
        $this->currency = new Currency();
        $this->currency->setReference('currency');
        $this->currency->setLabel('Currency');
        $this->dao->write($this->currency);
        $this->paymentMode = new PaymentMode();
        $this->paymentMode->setReference('payment');
        $this->paymentMode->setLabel('Payment');
        $this->dao->write($this->paymentMode);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        foreach ([CashSessionCat::class, CashSessionCustBalance::class,
                        CashSessionTax::class, CashSession::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->delete($this->cashRegister);
        $this->dao->delete($this->tax);
        $this->dao->delete($this->tax2);
        $this->dao->delete($this->currency);
        $this->dao->delete($this->paymentMode);
        $this->dao->commit();
        $this->dao->close();
    }

    // Open date tests
    //////////////////

    public function testSetOpenDate() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getOpenDate());
        $date = new \DateTime();
        $session->setOpenDate($date);
        $this->assertTrue(DateUtils::equals($date, $session->getOpenDate()));
        $session->setOpenDate($date);
        $this->assertTrue(DateUtils::equals($date, $session->getOpenDate()));
        // No exception thrown
    }

    /** @depends testSetOpenDate */
    public function testOverwriteOpenDate() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Open date is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $date = new \DateTime();
        $date2 = new \DateTime();
        $date2->add(new \DateInterval('PT3H'));
        $session->setOpenDate($date);
        $session->setOpenDate($date2);
    }

    /** @depends testSetOpenDate */
    public function testIsOpened() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertFalse($session->isOpened());
        $session->setOpenDate(new \DateTime());
        $this->assertTrue($session->isOpened());
    }

    // Close date tests
    ///////////////////

    public function testSetCloseDate() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getCloseDate());
        $date = new \DateTime();
        $session->setCloseDate($date);
        $this->assertTrue(DateUtils::equals($date, $session->getCloseDate()));
        $session->setCloseDate($date);
        $this->assertTrue(DateUtils::equals($date, $session->getCloseDate()));
        // No exception thrown
    }

    /** @depends testSetCloseDate */
    public function testOverwriteCloseDate() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Close date is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $date = new \DateTime();
        $date2 =  new \DateTime();
        $date2->add(new \DateInterval('PT3H'));
        $session->setCloseDate($date);
        $session->setCloseDate($date2);
    }

    /** @depends testSetOpenDate */
    public function testIsClosed() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertFalse($session->isClosed());
        $session->setCloseDate(new \DateTime());
        $this->assertTrue($session->isClosed());
    }

    // Open cash tests
    //////////////////

    public function testSetOpenCash() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getOpenCash());
        $openCash = 10.0;
        $session->setOpenCash($openCash);
        $this->assertEquals(10.0, $session->getOpenCash());
        $session->setOpenCash($openCash);
        $this->assertEquals(10.0, $session->getOpenCash());
        // No exception thrown.
    }

    /** @depends testSetOpenCash */
    public function testOverwriteOpenCash() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Open cash is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $date = new \DateTime();
        $openCash = 10.0;
        $session->setOpenCash($openCash);
        $session->setOpenCash($openCash + 2.5);
    }

    // Close cash tests
    //////////////////

    public function testSetCloseCash() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getCloseCash());
        $closeCash = 10.0;
        $session->setCloseCash($closeCash);
        $this->assertEquals(10.0, $session->getCloseCash());
        $session->setCloseCash($closeCash);
        $this->assertEquals(10.0, $session->getCloseCash());
        // No exception thrown.
    }

    /** @depends testSetCloseCash */
    public function testOverwriteCloseCash() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Close cash is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $date = new \DateTime();
        $closeCash = 10.0;
        $session->setCloseCash($closeCash);
        $session->setCloseCash($closeCash + 2.5);
    }

    // Expected cash tests
    //////////////////////

    public function testSetExpectedCash() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getExpectedCash());
        $expectedCash = 10.0;
        $session->setExpectedCash($expectedCash);
        $this->assertEquals(10.0, $session->getExpectedCash());
    }

    // Ticket count tests
    //////////////////////

    public function testSetTicketCount() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getTicketCount());
        $ticketCount = 3;
        $session->setticketCount($ticketCount);
        $this->assertEquals(3, $session->getTicketCount());
        $session->setticketCount($ticketCount);
        $this->assertEquals(3, $session->getTicketCount());
        // No exception thrown.
    }

    /** @depends testSetTicketCount */
    public function testOverwriteTicketCount() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Ticket count is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $ticketCount = 3;
        $session->setticketCount($ticketCount);
        $session->setticketCount(5);
    }

    // Customer count tests
    //////////////////////

    public function testSetCustCount() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getCustCount());
        $custCount = 3;
        $session->setCustCount($custCount);
        $this->assertEquals(3, $session->getCustCount());
        $session->setCustCount($custCount);
        $this->assertEquals(3, $session->getCustCount());
        // No exception thrown.
    }

    /** @depends testSetCustCount */
    public function testOverwriteCustCount() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Customer count is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $custCount = 3;
        $session->setCustCount($custCount);
        $session->setCustCount(5);
    }

    // CS ro tests
    //////////////

    public function testSetCS() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->assertNull($session->getCS());
        $cs = 10.0;
        $session->setCS($cs);
        $this->assertEquals(10.0, $session->getCS());
        $session->setCS($cs);
        $this->assertEquals(10.0, $session->getCS());
        // No exception thrown.
    }

    /** @depends testSetCS */
    public function testOverwriteCS() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Consolidated sales is read only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $cs = 10.0;
        $session->setCS($cs);
        $session->setCS(5);
    }

    public function testGetLoadKey() {
        $valid = ['cashRegister' => 1, 'sequence' => '2'];
        $validLoad = CashSession::getLoadKey($valid);
        $this->assertTrue(array_key_exists('cashRegister', $validLoad));
        $this->assertTrue(array_key_exists('sequence', $validLoad));
        $this->assertEquals(1, $validLoad['cashRegister']);
        $this->assertEquals(2, $validLoad['sequence']);
        $invalid = ['notRef' => 'ref'];
        $invalidLoad = CashSession::getLoadKey($invalid);
        $this->assertNull($invalidLoad);
    }

    private function initEmptySession() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        return $session;
    }

    /** Get a minimal valid CashSession struct. */
    private function emptyStruct() {
        return ['cashRegister' => $this->cashRegister->getId(),
                'sequence' => 1,
                'taxes' => [], 'payments' => [], 'catSales' => [],
                'custBalances' => []];
    }

    public function testMergeDefaults() {
        $session = $this->initEmptySession();
        $this->dao->write($session);
        $this->dao->commit();
        $struct = $this->emptyStruct();
        $session = new CashSession();
        $session->merge($struct, $this->dao);
        $this->assertEquals($this->cashRegister->getId(), $session->getCashRegister()->getId());
        $this->assertEquals(1, $session->getSequence());
    }

    /** @depends testMergeDefaults
     * Check that sums are not read from struct
     * when the session is not closed. */
    public function testMergeIgnoreSums() {
        $session = $this->initEmptySession();
        $session->setCSPeriod(10.0);
        $session->setCSFYear(20.0);
        $session->setCSPerpetual(30.0);
        $tax = new CashSessionTax();
        $tax->setTax($this->tax);
        $tax->setTaxRate($this->tax->getRate());
        $tax->setBase(10.0);
        $tax->setAmount(1.0);
        $tax->setBasePeriod(20.0);
        $tax->setAmountPeriod(2.0);
        $tax->setBaseFYear(30.0);
        $tax->setAmountFYear(3.0);
        $session->addTax($tax);
        $this->dao->write($session);
        $this->dao->commit();
        $struct = $this->emptyStruct();
        $struct['csPeriod'] = 15.0;
        $struct['csFYear'] = 25.0;
        $struct['csPerpetual'] = 35.0;
        $struct['taxes'] = [['tax' => $this->tax->getId(),
                    'base' => 15.0, 'amount' => 1.5,
                    'basePeriod' => 25, 'amountPeriod' => 2.5,
                    'baseFYear' => 35, 'amountFYear' => 3.5]];
        $session->merge($struct, $this->dao);
        $this->assertEquals(10.0, $session->getCSPeriod());
        $this->assertEquals(20.0, $session->getCSFYear());
        $this->assertEquals(30.0, $session->getCSPerpetual());
        $this->assertEquals(1, count($session->getTaxes()));
        $taxLine = $session->getTaxes()->get(0);
        $this->assertEquals(10.0, $taxLine->getBase());
        $this->assertEquals(1.0, $taxLine->getAmount());
        $this->assertEquals(20, $taxLine->getBasePeriod());
        $this->assertEquals(2.0, $taxLine->getAmountPeriod());
        $this->assertEquals(30.0, $taxLine->getBaseFYear());
        $this->assertEquals(3.0, $taxLine->getAmountFYear());
    }

    public function testMergeSums() {
        $session = $this->initEmptySession();
        $session->setCSPeriod(10.0);
        $session->setCSFYear(20.0);
        $tax = new CashSessionTax();
        $tax->setTax($this->tax);
        $tax->setTaxRate($this->tax->getRate());
        $tax->setBase(10.0);
        $tax->setAmount(1.0);
        $tax->setBasePeriod(20.0);
        $tax->setAmountPeriod(2.0);
        $tax->setBaseFYear(30.0);
        $tax->setAmountFYear(3.0);
        $session->addTax($tax);
        $this->dao->write($session);
        $this->dao->commit();
        $struct = $this->emptyStruct();
        $struct['csPeriod'] = 20.0;
        $struct['csFYear'] = 30.0;
        $struct['csPerpetual'] = 40;
        $struct['closeDate'] = DateUtils::toTimestamp(new \DateTime());
        $struct['taxes'] = [['tax' => $this->tax->getId(),
                    'base' => 15.0, 'amount' => 1.5,
                    'basePeriod' => 25, 'amountPeriod' => 2.5,
                    'baseFYear' => 35, 'amountFYear' => 3.5],
                ['tax' => $this->tax2->getId(),
                        'base' => 5.0, 'amount' => 1.0,
                        'basePeriod' => 6.0, 'amountPeriod' => 1.2,
                        'baseFYear' => 7.0, 'amountFYear' => 1.4]];
        $session = new CashSession();
        $session->merge($struct, $this->dao);
        $this->assertEquals(20.0, $session->getCSPeriod());
        $this->assertEquals(30.0, $session->getCSFYear());
        $this->assertEquals(40.0, $session->getCSPerpetual());
        $this->assertEquals(2, count($session->getTaxes()));
        $taxLine = $session->getTaxes()->get(0);
        $this->assertEquals($this->tax->getId(), $taxLine->getTax()->getId());
        $this->assertEquals(15.0, $taxLine->getBase());
        $this->assertEquals(1.5, $taxLine->getAmount());
        $this->assertEquals(25, $taxLine->getBasePeriod());
        $this->assertEquals(2.5, $taxLine->getAmountPeriod());
        $this->assertEquals(35.0, $taxLine->getBaseFYear());
        $this->assertEquals(3.5, $taxLine->getAmountFYear());
        $taxLine2 = $session->getTaxes()->get(1);
        $this->assertEquals($this->tax2->getId(), $taxLine2->getTax()->getId());
        $this->assertEquals(5.0, $taxLine2->getBase());
        $this->assertEquals(1.0, $taxLine2->getAmount());
        $this->assertEquals(6, $taxLine2->getBasePeriod());
        $this->assertEquals(1.2, $taxLine2->getAmountPeriod());
        $this->assertEquals(7.0, $taxLine2->getBaseFYear());
        $this->assertEquals(1.4, $taxLine2->getAmountFYear());
    }

    public function testMergeSumsNoPerpetualFirst() {
        $session = $this->initEmptySession();
        $session->setCS(5.0);
        $session->setCSPeriod(10.0);
        $session->setCSFYear(20.0);
        $tax = new CashSessionTax();
        $tax->setTax($this->tax);
        $tax->setTaxRate($this->tax->getRate());
        $tax->setBase(10.0);
        $tax->setAmount(1.0);
        $tax->setBasePeriod(20.0);
        $tax->setAmountPeriod(2.0);
        $tax->setBaseFYear(30.0);
        $tax->setAmountFYear(3.0);
        $session->addTax($tax);
        $this->dao->write($session);
        $this->dao->commit();
        $struct = $this->emptyStruct();
        $struct['cs'] = 10.0;
        $struct['csPeriod'] = 20.0;
        $struct['csFYear'] = 30.0;
        $struct['closeDate'] = DateUtils::toTimestamp(new \DateTime());
        $struct['taxes'] = [['tax' => $this->tax->getId(),
                    'base' => 15.0, 'amount' => 1.5,
                    'basePeriod' => 25, 'amountPeriod' => 2.5,
                    'baseFYear' => 35, 'amountFYear' => 3.5],
                ['tax' => $this->tax2->getId(),
                        'base' => 5.0, 'amount' => 1.0,
                        'basePeriod' => 6.0, 'amountPeriod' => 1.2,
                        'baseFYear' => 7.0, 'amountFYear' => 1.4]];
        $session = new CashSession();
        $session->merge($struct, $this->dao);
        $this->assertEquals(10.0, $session->getCS());
        $this->assertEquals(20.0, $session->getCSPeriod());
        $this->assertEquals(30.0, $session->getCSFYear());
        $this->assertEquals(10.0, $session->getCSPerpetual());
        $this->assertEquals(2, count($session->getTaxes()));
        $taxLine = $session->getTaxes()->get(0);
        $this->assertEquals($this->tax->getId(), $taxLine->getTax()->getId());
        $this->assertEquals(15.0, $taxLine->getBase());
        $this->assertEquals(1.5, $taxLine->getAmount());
        $this->assertEquals(25, $taxLine->getBasePeriod());
        $this->assertEquals(2.5, $taxLine->getAmountPeriod());
        $this->assertEquals(35.0, $taxLine->getBaseFYear());
        $this->assertEquals(3.5, $taxLine->getAmountFYear());
        $taxLine2 = $session->getTaxes()->get(1);
        $this->assertEquals($this->tax2->getId(), $taxLine2->getTax()->getId());
        $this->assertEquals(5.0, $taxLine2->getBase());
        $this->assertEquals(1.0, $taxLine2->getAmount());
        $this->assertEquals(6, $taxLine2->getBasePeriod());
        $this->assertEquals(1.2, $taxLine2->getAmountPeriod());
        $this->assertEquals(7.0, $taxLine2->getBaseFYear());
        $this->assertEquals(1.4, $taxLine2->getAmountFYear());
    }

    public function testMergeSumsNoPerpetualNext() {
        $session = $this->initEmptySession();
        $session->setCS(5.0);
        $session->setCSPeriod(10.0);
        $session->setCSFYear(20.0);
        $session->setCSPerpetual(30.0);
        $tax = new CashSessionTax();
        $tax->setTax($this->tax);
        $tax->setTaxRate($this->tax->getRate());
        $tax->setBase(10.0);
        $tax->setAmount(1.0);
        $tax->setBasePeriod(20.0);
        $tax->setAmountPeriod(2.0);
        $tax->setBaseFYear(30.0);
        $tax->setAmountFYear(3.0);
        $session->addTax($tax);
        $this->dao->write($session);
        $this->dao->commit();
        $struct = $this->emptyStruct();
        $struct['sequence'] = 2;
        $struct['cs'] = 10.0;
        $struct['csPeriod'] = 20.0;
        $struct['csFYear'] = 30.0;
        $struct['closeDate'] = DateUtils::toTimestamp(new \DateTime());
        $struct['taxes'] = [['tax' => $this->tax->getId(),
                    'base' => 15.0, 'amount' => 1.5,
                    'basePeriod' => 25, 'amountPeriod' => 2.5,
                    'baseFYear' => 35, 'amountFYear' => 3.5],
                ['tax' => $this->tax2->getId(),
                        'base' => 5.0, 'amount' => 1.0,
                        'basePeriod' => 6.0, 'amountPeriod' => 1.2,
                        'baseFYear' => 7.0, 'amountFYear' => 1.4]];
        $session = new CashSession();
        $session->merge($struct, $this->dao);
        $this->assertEquals(10.0, $session->getCS());
        $this->assertEquals(20.0, $session->getCSPeriod());
        $this->assertEquals(30.0, $session->getCSFYear());
        $this->assertEquals(40.0, $session->getCSPerpetual());
        $this->assertEquals(2, count($session->getTaxes()));
        $taxLine = $session->getTaxes()->get(0);
        $this->assertEquals($this->tax->getId(), $taxLine->getTax()->getId());
        $this->assertEquals(15.0, $taxLine->getBase());
        $this->assertEquals(1.5, $taxLine->getAmount());
        $this->assertEquals(25, $taxLine->getBasePeriod());
        $this->assertEquals(2.5, $taxLine->getAmountPeriod());
        $this->assertEquals(35.0, $taxLine->getBaseFYear());
        $this->assertEquals(3.5, $taxLine->getAmountFYear());
        $taxLine2 = $session->getTaxes()->get(1);
        $this->assertEquals($this->tax2->getId(), $taxLine2->getTax()->getId());
        $this->assertEquals(5.0, $taxLine2->getBase());
        $this->assertEquals(1.0, $taxLine2->getAmount());
        $this->assertEquals(6, $taxLine2->getBasePeriod());
        $this->assertEquals(1.2, $taxLine2->getAmountPeriod());
        $this->assertEquals(7.0, $taxLine2->getBaseFYear());
        $this->assertEquals(1.4, $taxLine2->getAmountFYear());
    }

    public function testToStructDates() {
        $session = $this->initEmptySession();
        $struct = $session->toStruct();
        $this->assertNull($struct['openDate']);
        $this->assertNull($struct['closeDate']);
        $date = new \DateTime();
        $date2 =  new \DateTime();
        $date2->add(new \DateInterval('PT3H'));
        $session->setOpenDate($date);
        $session->setCloseDate($date2);
        $struct = $session->toStruct();
        $this->assertEquals(DateUtils::toTimestamp($date), $struct['openDate']);
        $this->assertEquals(DateUtils::toTimestamp($date2), $struct['closeDate']);
    }

    /** Test the old format from Pasteque Desktop 8.0-8.6 which used hardcoded
     * payment modes. */
    public function testPaymentLoadDesktopOld() {
        $struct = ['amount' => 10, 'currencyAmount' => 12, 'cashSession' => null,
                'currency' => $this->currency->getId()];
        $struct['desktop'] = true;
        $struct['type'] = $this->paymentMode->getReference();
        $session = new CashSession();
        $cashPayment = CashSessionPayment::loadOrCreate($struct, $session,
                $this->dao);
        $cashPayment->merge($struct, $this->dao);
        $this->assertNotNull($cashPayment->getPaymentMode());
        $this->assertEquals($this->paymentMode->getReference(),
                $cashPayment->getPaymentMode()->getReference());
    }
}
