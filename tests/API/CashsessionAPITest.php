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

use \Pasteque\Server\API\CashsessionAPI;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\CashSessionTax;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

/** CashsessionAPI with access to protected methods. */
class CashsessionAPIProxy extends CashsessionAPI
{
    const MODEL_NAME = 'Pasteque\Server\Model\CashSession';

    public function updateEOSTicketProxy($lastTicket) {
        return $this->updateEOSTicket($lastTicket);
    }
}

class CashsessionAPITest extends TestCase
{
    private $dao;
    private $api;
    private $tax;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new CashsessionAPIProxy($this->dao);
        $this->cashRegister = new CashRegister();
        $this->cashRegister->setReference('register');
        $this->cashRegister->setLabel('Register');
        $this->dao->write($this->cashRegister);
        $this->tax = new Tax();
        $this->tax->setLabel('tax');
        $this->tax->setRate(0.1);
        $this->dao->write($this->tax);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $taxes = $this->dao->search(CashSessionTax::class);
        foreach ($taxes as $tax) { $this->dao->delete($tax); }
        $all = $this->dao->search(CashSession::class);
        foreach($all as $record) { $this->dao->delete($record); }
        $ftkt = $this->dao->search(FiscalTicket::class);
        foreach($ftkt as $tkt) { $this->dao->delete($tkt); }
        $this->dao->delete($this->cashRegister);
        $this->dao->delete($this->tax);
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetEmpty() {
        $session = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($session);
        $this->assertEquals($this->cashRegister->getId(),
                $session->getCashRegister()->getId());
        $this->assertEquals(1, $session->getSequence());
        $this->assertFalse($session->isOpened());
        $this->assertFalse($session->isClosed());
    }

    /** @depends testGetEmpty */
    public function testGet() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(5);
        $session2 = new CashSession();
        $session2->setCashRegister($this->cashRegister);
        $session2->setSequence(4);
        $this->dao->write($session);
        $this->dao->write($session2);
        $this->dao->commit();
        // Get inexistent sequence
        $this->assertNull($this->api->get(array('cashRegister' => $this->cashRegister->getId(), 'sequence' => 1)));
        // Get inexistent cash register
        $this->assertNull($this->api->get(array('cashRegister' => 0, 'sequence' => 5)));
        // Get existing session
        $read = $this->api->get(array('cashRegister' => $this->cashRegister->getId(), 'sequence' => 4));
        $this->assertNotNull($read);
        $this->assertEquals($this->cashRegister->getId(),
                $read->getCashRegister()->getId());
        $this->assertEquals(4, $read->getSequence());
        // Get last existing
        $read = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($read);
        $this->assertEquals($this->cashRegister->getId(),
                $read->getCashRegister()->getId());
        $this->assertEquals(5, $read->getSequence());
    }

    public function testWrite() {
        // Write empty
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->api->write($session);
        $this->assertEquals($this->cashRegister->getId(), $session->getCashRegister()->getId());
        $this->assertEquals(1, $session->getSequence());
        // Open and write
        $openDate = new \DateTime();
        $openDate->setDate(2017, 1, 2);
        $openDate->setTime(14, 0);
        $session->setOpenDate($openDate);
        $this->api->write($session);
        $this->assertEquals($openDate->getTimestamp(),
                $session->getOpenDate()->getTimestamp());
        // Close and check FiscalTicket
        $closeDate = new \DateTime();
        $closeDate->add(new \DateInterval('PT3H'));
        $session->setCloseDate($closeDate);
        $this->api->write($session);
        $now = new \DateTime();
        $readfTkt = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_ZTICKET,
                'sequence' => FiscalTicket::getZTicketSequence($session),
                'number' => 1]);
        $this->assertNotNull($readfTkt);
        $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_ZTICKET,
                'sequence' => FiscalTicket::getZTicketSequence($session),
                'number' => 0]);
        $this->assertNotNull($readfTkt);
        $this->assertNotNull($readEOSTkt);
        $this->assertLessThan(3,
                abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
        $this->assertTrue(DateUtils::equals($readfTkt->getDate(),
                $readEOSTkt->getDate()));
        $this->assertTrue($readfTkt->checkSignature(null));
        $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
    }

    /** Open the session, set some cs and close. */
    private function openAndClose($session, $cs, $closeType = null) {
        $openDate = new \DateTime();
        $openDate->setDate(2017, 1, 2);
        $openDate->setTime(14, 0);
        $session->setOpenDate($openDate);
        $this->api->write($session);
        $closeDate = new \DateTime();
        $closeDate->add(new \DateInterval('PT3H'));
        $session->setCloseDate($closeDate);
        $session->setCS($cs);
        $session->setCSPeriod($session->getCSPeriod() + $cs);
        $session->setCSFYear($session->getCSFYear() + $cs);
        $session->setCSPerpetual($session->getCSPerpetual() + $cs);
        $tax = null;
        if (count($session->getTaxes()) > 0) {
            $tax = $session->getTaxes()->get(0);
        } else {
            $tax = new CashSessionTax();
            $tax->setTax($this->tax);
            $tax->setTaxRate($this->tax->getRate());
            $session->addTax($tax);
        }
        $amount = $cs * $this->tax->getRate();
        $tax->setBase($cs);
        $tax->setAmount($amount);
        $tax->setBasePeriod($tax->getBasePeriod() + $cs);
        $tax->setAmountPeriod($tax->getAmountPeriod() + $amount);
        $tax->setBaseFYear($tax->getBaseFYear() + $cs);
        $tax->setAmountFYear($tax->getAmountFYear() + $amount);
        if ($closeType !== null) { $session->setCloseType($closeType); }
        $this->api->write($session);
    }

    /** @depends testWrite
     * Check that a new session is created with the right sums. */
    public function testNextSessionSimple() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->openAndClose($session, 10.0);
        $this->assertEquals(10.0, $session->getCS());
        $this->assertEquals(10.0, $session->getCSPeriod());
        $this->assertEquals(10.0, $session->getCSFYear());
        $this->assertEquals(10.0, $session->getCSPerpetual());
        $this->assertEquals(1, count($session->getTaxes()));
        $tax = $session->getTaxes()->get(0);
        $this->assertEquals(10.0, $tax->getBase());
        $this->assertEquals(1.0, $tax->getAmount());
        $this->assertEquals(10.0, $tax->getBasePeriod());
        $this->assertEquals(1.0, $tax->getAmountPeriod());
        $this->assertEquals(10.0, $tax->getBaseFYear());
        $this->assertEquals(1.0, $tax->getAmountFYear());
        // Check next session
        $next = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($next);
        $this->assertNull($next->getCS());
        $this->assertEquals(10.0, $next->getCSPeriod());
        $this->assertEquals(10.0, $next->getCSFYear());
        $this->assertEquals(10.0, $next->getCSPerpetual());
        $this->assertEquals(1, count($next->getTaxes()));
        $tax = $next->getTaxes()->get(0);
        $this->assertEquals(0.0, $tax->getBase());
        $this->assertEquals(0.0, $tax->getAmount());
        $this->assertEquals(10.0, $tax->getBasePeriod());
        $this->assertEquals(1.0, $tax->getAmountPeriod());
        $this->assertEquals(10.0, $tax->getBaseFYear());
        $this->assertEquals(1.0, $tax->getAmountFYear());
        // Redo to check sum
        $this->openAndClose($next, 20.0);
        $this->assertEquals(20.0, $next->getCS());
        $this->assertEquals(30.0, $next->getCSPeriod());
        $this->assertEquals(30.0, $next->getCSFYear());
        $this->assertEquals(30.0, $next->getCSPerpetual());
        $this->assertEquals(1, count($next->getTaxes()));
        $tax = $next->getTaxes()->get(0);
        $this->assertEquals(20.0, $tax->getBase());
        $this->assertEquals(2.0, $tax->getAmount());
        $this->assertEquals(30.0, $tax->getBasePeriod());
        $this->assertEquals(3.0, $tax->getAmountPeriod());
        $this->assertEquals(30.0, $tax->getBaseFYear());
        $this->assertEquals(3.0, $tax->getAmountFYear());
        $next2 = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($next2);
        $this->assertNull($next2->getCS());
        $this->assertEquals(30.0, $next2->getCSPeriod());
        $this->assertEquals(30.0, $next2->getCSFYear());
        $this->assertEquals(30.0, $next2->getCSPerpetual());
        $this->assertEquals(1, count($next2->getTaxes()));
        $tax = $next2->getTaxes()->get(0);
        $this->assertEquals(0.0, $tax->getBase());
        $this->assertEquals(0.0, $tax->getAmount());
        $this->assertEquals(30.0, $tax->getBasePeriod());
        $this->assertEquals(3.0, $tax->getAmountPeriod());
        $this->assertEquals(30.0, $tax->getBaseFYear());
        $this->assertEquals(3.0, $tax->getAmountFYear());
    }

    /** @depends testNextSessionSimple */
    public function testNextSessionPeriod() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->openAndClose($session, 10.0, CashSession::CLOSE_PERIOD);
        $this->assertEquals(10.0, $session->getCS());
        $this->assertEquals(10.0, $session->getCSPeriod());
        $this->assertEquals(10.0, $session->getCSFYear());
        $this->assertEquals(10.0, $session->getCSPerpetual());
        $this->assertEquals(1, count($session->getTaxes()));
        $tax = $session->getTaxes()->get(0);
        $this->assertEquals(10.0, $tax->getBase());
        $this->assertEquals(1.0, $tax->getAmount());
        $this->assertEquals(10.0, $tax->getBasePeriod());
        $this->assertEquals(1.0, $tax->getAmountPeriod());
        $this->assertEquals(10.0, $tax->getBaseFYear());
        $this->assertEquals(1.0, $tax->getAmountFYear());
        // Check next session
        $next = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($next);
        $this->assertNull($next->getCS());
        $this->assertEquals(0.0, $next->getCSPeriod());
        $this->assertEquals(10.0, $next->getCSFYear());
        $this->assertEquals(10.0, $next->getCSPerpetual());
        $this->assertEquals(1, count($next->getTaxes()));
        $tax = $next->getTaxes()->get(0);
        $this->assertEquals(0.0, $tax->getBase());
        $this->assertEquals(0.0, $tax->getAmount());
        $this->assertEquals(0.0, $tax->getBasePeriod());
        $this->assertEquals(0.0, $tax->getAmountPeriod());
        $this->assertEquals(10.0, $tax->getBaseFYear());
        $this->assertEquals(1.0, $tax->getAmountFYear());
        // Redo to check sum
        $this->openAndClose($next, 20.0, CashSession::CLOSE_PERIOD);
        $this->assertEquals(20.0, $next->getCS());
        $this->assertEquals(20.0, $next->getCSPeriod());
        $this->assertEquals(30.0, $next->getCSFYear());
        $this->assertEquals(30.0, $next->getCSPerpetual());
        $this->assertEquals(1, count($next->getTaxes()));
        $tax = $next->getTaxes()->get(0);
        $this->assertEquals(20.0, $tax->getBase());
        $this->assertEquals(2.0, $tax->getAmount());
        $this->assertEquals(20.0, $tax->getBasePeriod());
        $this->assertEquals(2.0, $tax->getAmountPeriod());
        $this->assertEquals(30.0, $tax->getBaseFYear());
        $this->assertEquals(3.0, $tax->getAmountFYear());
        $next2 = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($next2);
        $this->assertNull($next2->getCS());
        $this->assertEquals(0.0, $next2->getCSPeriod());
        $this->assertEquals(30.0, $next2->getCSFYear());
        $this->assertEquals(1, count($next2->getTaxes()));
        $tax = $next2->getTaxes()->get(0);
        $this->assertEquals(0.0, $tax->getBase());
        $this->assertEquals(0.0, $tax->getAmount());
        $this->assertEquals(0.0, $tax->getBasePeriod());
        $this->assertEquals(0.0, $tax->getAmountPeriod());
        $this->assertEquals(30.0, $tax->getBaseFYear());
        $this->assertEquals(3.0, $tax->getAmountFYear());
    }

    /** @depends testNextSessionSimple */
    public function testNextSessionFYear() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $this->openAndClose($session, 10.0, CashSession::CLOSE_FYEAR);
        $this->assertEquals(10.0, $session->getCS());
        $this->assertEquals(10.0, $session->getCSPeriod());
        $this->assertEquals(10.0, $session->getCSFYear());
        $this->assertEquals(10.0, $session->getCSPerpetual());
        $this->assertEquals(1, count($session->getTaxes()));
        $tax = $session->getTaxes()->get(0);
        $this->assertEquals(10.0, $tax->getBase());
        $this->assertEquals(1.0, $tax->getAmount());
        $this->assertEquals(10.0, $tax->getBasePeriod());
        $this->assertEquals(1.0, $tax->getAmountPeriod());
        $this->assertEquals(10.0, $tax->getBaseFYear());
        $this->assertEquals(1.0, $tax->getAmountFYear());
        // Check next session
        $next = $this->api->get($this->cashRegister->getId());
        $this->assertNotNull($next);
        $this->assertEquals(0.0, $next->getCS());
        $this->assertEquals(0.0, $next->getCSPeriod());
        $this->assertEquals(0.0, $next->getCSFYear());
        $this->assertEquals(10.0, $next->getCSPerpetual());
        $this->assertEquals(0, count($next->getTaxes()));
    }

    /** @depends testWrite */
    public function testWriteClosed() {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Trying to close a session that was not registered as open.');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $openDate = new \DateTime();
        $openDate->setDate(2017, 1, 2);
        $openDate->setTime(14, 0);
        $closeDate = new \DateTime();
        $closeDate->setDate(2017, 1, 2);
        $closeDate->setTime(22, 0);
        $session->setCloseDate($closeDate);
        try {
            $this->api->write($session);
        } catch (\BadMethodCallException $e) {
            $now = new \DateTime();
            $searchSession = $this->dao->search(CashSession::class);
            $this->assertEquals(0, count($searchSession));
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_ZTICKET,
                    'sequence' => FiscalTicket::getFailureZTicketSequence($session),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_ZTICKET,
                    'sequence' => FiscalTicket::getFailureZTicketSequence($session),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $this->assertNotNull($readEOSTkt);
            $this->assertLessThan(3,
                    abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertEquals($fdata['failure'], 'Trying to close a session that was not registered as open.');
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
            throw $e;
        }
    }

    /** @depends testWriteClosed */
    public function testFailTwice() {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Trying to close a session that was not registered as open.');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $openDate = new \DateTime();
        $openDate->setDate(2017, 1, 2);
        $openDate->setTime(14, 0);
        $closeDate = new \DateTime();
        $closeDate->setDate(2017, 1, 2);
        $closeDate->setTime(22, 0);
        $session->setCloseDate($closeDate);
        try {
            $this->api->write($session);
        } catch (\BadMethodCallException $e) {
            // This is expected.
            try {
                $this->api->write($session);
            } catch (\BadMethodCallException $e2) {
                $now = new \DateTime();
                $searchSession = $this->dao->search(CashSession::class);
                $this->assertEquals(0, count($searchSession));
                $readfTkt = $this->dao->read(FiscalTicket::class, [
                        'type' => FiscalTicket::TYPE_ZTICKET,
                        'sequence' => FiscalTicket::getFailureZTicketSequence($session),
                        'number' => 1]);
                $readfTkt2 = $this->dao->read(FiscalTicket::class, [
                        'type' => FiscalTicket::TYPE_ZTICKET,
                        'sequence' => FiscalTicket::getFailureZTicketSequence($session),
                        'number' => 2]);
                $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                        'type' => FiscalTicket::TYPE_ZTICKET,
                        'sequence' => FiscalTicket::getFailureZTicketSequence($session),
                        'number' => 0]);
                $this->assertNotNull($readfTkt);
                $this->assertNotNull($readfTkt2);
                $this->assertNotNull($readEOSTkt);
                $this->assertLessThan(3,
                        abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
                $this->assertLessThan(3,
                        abs($now->getTimestamp() - $readfTkt2->getDate()->getTimestamp()));
                $fdata = json_decode($readfTkt->getContent(), true);
                $this->assertNotNull($fdata);
                $this->assertEquals($fdata['failure'], 'Trying to close a session that was not registered as open.');
                $fdata2 = json_decode($readfTkt2->getContent(), true);
                $this->assertNotNull($fdata2);
                $this->assertEquals($fdata2['failure'], 'Trying to close a session that was not registered as open.');
                $this->assertTrue($readfTkt->checkSignature(null));
                $this->assertTrue($readfTkt2->checkSignature($readfTkt));
                $this->assertTrue($readEOSTkt->checkSignature($readfTkt2));
                throw $e;
            }
        }
    }

    /** @depends testWrite */
    public function testCloseTwice() {
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $openDate = new \DateTime();
        $openDate->setDate(2017, 1, 2);
        $openDate->setTime(14, 0);
        $this->api->write($session);
        $closeDate = new \DateTime();
        $closeDate->setDate(2017, 1, 2);
        $closeDate->setTime(22, 0);
        $session->setCloseDate($closeDate);
        $this->api->write($session);
        $fTktCount = count($this->dao->search(FiscalTicket::class));
        $sessCount = count($this->dao->search(CashSession::class));
        $this->api->write($session);
        // Check that there is no new cash session
        $this->assertEquals($sessCount,
                count($this->dao->search(CashSession::class)));
        // Check that there is no new fiscal ticket
        $this->assertEquals($fTktCount,
                count($this->dao->search(FiscalTicket::class)));
    }

    /** @depends testWrite */
    public function testOverwriteClosed() {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Closed sessions are read-only');
        $session = new CashSession();
        $session->setCashRegister($this->cashRegister);
        $session->setSequence(1);
        $openDate = new \DateTime();
        $openDate->setDate(2017, 1, 2);
        $openDate->setTime(14, 0);
        $this->api->write($session);
        $closeDate = new \DateTime();
        $closeDate->setDate(2017, 1, 2);
        $closeDate->setTime(22, 0);
        $session->setCloseDate($closeDate);
        $this->api->write($session);
        $closeDate->setTime(22, 30);
        $session->setCloseDate($closeDate);
        try {
            $this->api->write($session);
        } catch (\BadMethodCallException $e) {
            $now = new \DateTime();
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_ZTICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($session),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_ZTICKET,
                    'sequence' => FiscalTicket::getFailureZTicketSequence($session),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $this->assertNotNull($readEOSTkt);
            $this->assertLessThan(3,
                    abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertEquals($fdata['failure'], 'Closed sessions are read-only.');
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
            throw $e;
        }
    }

    public function testUpdateEOSEmpty() {
        $this->api->updateEOSTicketProxy(null);
        $this->dao->commit();
        $eos = $this->dao->count(FiscalTicket::class);
        $this->assertEquals(0, $eos);
    }

    public function testUpdateEOSCreate() {
        $ft = new FiscalTicket();
        $ft->setType(FiscalTicket::TYPE_ZTICKET);
        $ft->setSequence('00001');
        $ft->setNumber(1);
        $ft->setDate(new \DateTime());
        $ft->setContent('Meh');
        $ft->sign(null);
        $this->api->updateEOSTicketProxy($ft);
        $this->dao->commit();
        $eos = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_ZTICKET,
                'sequence' => '00001',
                'number' => 0]);
        $this->assertNotNull($eos);
        $this->assertTrue(DateUtils::equals($ft->getDate(), $eos->getDate()));
        $this->assertFalse($eos->checkSignature(null));
        $this->assertTrue($eos->checkSignature($ft));
    }

    public function testUpdateEOS() {
        $ft = new FiscalTicket();
        $ft->setType(FiscalTicket::TYPE_ZTICKET);
        $ft->setSequence('00001');
        $ft->setNumber(1);
        $ft->setDate(new \DateTime());
        $ft->setContent('Meh');
        $ft->sign(null);
        $this->dao->write($ft);
        $this->api->updateEOSTicketProxy($ft);
        $this->dao->commit();
        $ft2 = new FiscalTicket();
        $ft2->setType(FiscalTicket::TYPE_ZTICKET);
        $ft2->setSequence('00001');
        $ft2->setNumber(2);
        $ft2->setDate(new \DateTime());
        $ft2->setContent('Meh too');
        $ft2->sign($ft);
        $this->dao->write($ft2);
        $this->api->updateEOSTicketProxy($ft2);
        $this->dao->commit();
        $eos = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_ZTICKET,
                'sequence' => '00001',
                'number' => 0]);
        $this->assertNotNull($eos);
        $this->assertTrue(DateUtils::equals($ft2->getDate(), $eos->getDate()));
        $this->assertFalse($eos->checkSignature($ft));
        $this->assertTrue($eos->checkSignature($ft2));
    }

}
