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

use \Pasteque\Server\API\CustomerAPI;
use \Pasteque\Server\API\TicketAPI;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\InvalidRecordException;
use \Pasteque\Server\Exception\UnicityException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\Model\TicketLine;
use \Pasteque\Server\Model\TicketTax;
use \Pasteque\Server\Model\TicketPayment;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");
require_once(dirname(dirname(__FILE__)) . '/common_ticket.php');

/** TicketAPI with access to protected methods. */
class TicketAPIProxy extends TicketAPI
{
    const MODEL_NAME = 'Pasteque\Server\Model\Ticket';

    public function updateEOSTicketProxy($lastTicket) {
        return $this->updateEOSTicket($lastTicket);
    }
}

class TicketAPITest extends TestCase
{
    private $dao;
    private $api;
    private $tax;
    private $cat;
    private $prd;
    private $cash;
    private $session;
    private $pm;
    private $curr;
    private $role;
    private $user;

    private function sampleTkt() {
        return [
            'cashRegister' => $this->session->getCashRegister()->getId(),
            'sequence' => $this->session->getSequence(),
            'number' => 1,
            'date' => new \DateTime('2018-01-01 8:05'),
                'user' => $this->user->getId(),
                'lines' => [
                    ['dispOrder' => 1,
                    'taxedUnitPrice' => 11.0,
                    'quantity' => 1, 'tax' => $this->tax->getId(),
                    'product' => $this->prd->getid(),
                    'taxRate' => 0.1, 'taxedPrice' => 11.0,
                    'finalTaxedPrice' => 11.0]
                    ],
                'taxes' => [
                        ['tax' => $this->tax->getId(),
                        'base' => 10.0,
                        'amount' => 1.0],
                ],
                'payments' => [
                        ['paymentMode' => $this->pm->getId(),
                        'currency' => $this->curr->getId(),
                        'dispOrder' => 1,
                        'amount' => 11.0,
                        'currencyAmount' => 11.0],
                ],
                'taxedPrice' => 11.0,
                'finalPrice' => 10.0,
                'finalTaxedPrice' => 11.0,
        ];
    }

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new TicketAPIProxy($this->dao);
        $this->cat = new Category();
        $this->cat->setReference('category');
        $this->cat->setLabel('Category');
        $this->dao->write($this->cat);
        $this->tax= new Tax();
        $this->tax->setLabel('VAT');
        $this->tax->setRate(0.1);
        $this->dao->write($this->tax);
        $this->prd = new Product();
        $this->prd->setReference('product');
        $this->prd->setLabel('Product');
        $this->prd->setTax($this->tax);
        $this->prd->setCategory($this->cat);
        $this->prd->setPriceSell(1.0);
        $this->dao->write($this->prd);
        $this->pm = new PaymentMode();
        $this->pm->setReference('pm');
        $this->pm->setLabel('Payment mode');
        $this->dao->write($this->pm);
        $this->curr = new Currency();
        $this->curr->setReference('curr');
        $this->curr->setLabel('Currency');
        $this->curr->setMain(true);
        $this->dao->write($this->curr);
        $this->cash = new CashRegister();
        $this->cash->setReference('cash');
        $this->cash->setLabel('Cash');
        $this->dao->write($this->cash);
        $this->role = new Role();
        $this->role->setName('role');
        $this->dao->write($this->role);
        $this->user = new User();
        $this->user->setName('user');
        $this->user->setRole($this->role);
        $this->dao->write($this->user);
        $this->session = new CashSession();
        $this->session->setCashRegister($this->cash);
        $this->session->setSequence(1);
        $this->session->setOpenDate(new \DateTime('2018-01-01 8:00'));
        $this->dao->write($this->session);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        foreach ([FiscalTicket::class, TicketPayment::class, TicketTax::class,
                        TicketLine::class, Ticket::class, Customer::class,
                        CashSession::class, CashRegister::class, User::class,
                        Role::class, Product::class, Category::class,
                        Tax::class, PaymentMode::class, Currency::class]
                as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    private function getBaseTicket($number) {
        // Proxy for common_ticket
        return getBaseTicket($this->cash, $this->session, $number, $this->user);
    }

    private function getBaseLine($dispOrder, $label, $taxedPrice) {
        // Proxy for common_ticket
        return getBaseLine($this->tax, $dispOrder, $label, $taxedPrice);
    }

    private function getBaseTax($base, $amount) {
        // Proxy for common_ticket
        return getBaseTax($this->tax, $base, $amount);
    }

    private function getBasePayment($dispOrder, $amount) {
        // Proxy for common_ticket
        return getBasePayment($this->pm, $this->curr, $dispOrder, $amount);
    }



    public function testUpdateEOSEmpty() {
        $this->api->updateEOSTicketProxy(null);
        $this->dao->commit();
        $eos = $this->dao->count(FiscalTicket::class);
        $this->assertEquals(0, $eos);
    }

    public function testUpdateEOSCreate() {
        $ft = new FiscalTicket();
        $ft->setType(FiscalTicket::TYPE_TICKET);
        $ft->setSequence('00001');
        $ft->setNumber(1);
        $ft->setDate(new \DateTime());
        $ft->setContent('Meh');
        $ft->sign(null);
        $this->api->updateEOSTicketProxy($ft);
        $this->dao->commit();
        $eos = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_TICKET,
                'sequence' => '00001',
                'number' => 0]);
        $this->assertNotNull($eos);
        $this->assertTrue(DateUtils::equals($ft->getDate(), $eos->getDate()));
        $this->assertFalse($eos->checkSignature(null));
        $this->assertTrue($eos->checkSignature($ft));
    }

    public function testUpdateEOS() {
        $ft = new FiscalTicket();
        $ft->setType(FiscalTicket::TYPE_TICKET);
        $ft->setSequence('00001');
        $ft->setNumber(1);
        $ft->setDate(new \DateTime());
        $ft->setContent('Meh');
        $ft->sign(null);
        $this->dao->write($ft);
        $this->api->updateEOSTicketProxy($ft);
        $this->dao->commit();
        $ft2 = new FiscalTicket();
        $ft2->setType(FiscalTicket::TYPE_TICKET);
        $ft2->setSequence('00001');
        $ft2->setNumber(2);
        $ft2->setDate(new \DateTime());
        $ft2->setContent('Meh too');
        $ft2->sign($ft);
        $this->dao->write($ft2);
        $this->api->updateEOSTicketProxy($ft2);
        $this->dao->commit();
        $eos = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_TICKET,
                'sequence' => '00001',
                'number' => 0]);
        $this->assertNotNull($eos);
        $this->assertTrue(DateUtils::equals($ft2->getDate(), $eos->getDate()));
        $this->assertFalse($eos->checkSignature($ft));
        $this->assertTrue($eos->checkSignature($ft2));
        }

    public function testSaveTicket0() {
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $tkt->setNumber(0);
        $exceptionThrown = false;
        try {
            $this->api->write($tkt);
        } catch (InvalidRecordException $e) {
            $exceptionThrown = true;
            $now = new \DateTime();
            // Check the exception
            $this->assertEquals(InvalidRecordException::CSTR_GENERATED,
                    $e->getConstraint());
            $this->assertEquals(Ticket::class, $e->getClass());
            $tktId = $e->getId();
            $this->assertEquals($this->session->getCashRegister()->getId(),
                    $tktId['cashRegister']);
            $this->assertEquals($this->session->getSequence(),
                    $tktId['sequence']);
            $this->assertEquals($tkt->getNumber(), $tktId['number']);
            // Check that the ticket is not saved.
            $searchTkt = $this->dao->search(Ticket::class);
            $this->assertEquals(0, count($searchTkt));
            // Check that the failure fiscal ticket is there.
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertLessThan(3,
                abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $this->assertEquals($fdata['failure'], 'Ticket number 0 is reserved.');
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
        }
        $this->assertTrue($exceptionThrown,
                'Expecting InvalidRecordException.');
    }

    /** @depends testUpdateEOSCreate */
    public function testSaveTicket() {
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $this->api->write($tkt);
        $now = new \DateTime();
        $readTkt = $this->dao->readSnapshot(Ticket::class, $tkt->getId());
        $this->assertNotNull($readTkt);
        assertTicketModelEqModel($tkt, $readTkt, $this);
        $readfTkt = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_TICKET,
                'sequence' => FiscalTicket::getTicketSequence($readTkt),
                'number' => 1]);
        $this->assertNotNull($readfTkt);
        $this->assertLessThan(3,
                abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
        $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                'type' => FiscalTicket::TYPE_TICKET,
                'sequence' => FiscalTicket::getTicketSequence($readTkt),
                'number' => 0]);
        $this->assertNotNull($readEOSTkt);
        $this->assertLessThan(3,
                abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
        $this->assertTrue($readfTkt->checkSignature(null));
        $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
    }

    /** @depends testSaveTicket
     * Test writing two identical tickets (without sharing references). */
    public function testSaveTicketTwice() {
        $tkt1 = new Ticket();
        $tkt1->merge($this->sampleTkt(), $this->dao);
        $this->api->write($tkt1);
        $fTktCount = count($this->dao->search(FiscalTicket::class));
        $exceptionThrown = false;
        $tkt2 = new Ticket();
        $tkt2->merge($this->sampleTkt(), $this->dao);
        $this->api->write($tkt2);
        // Check that there is no new ticket
        $searchTkt = $this->dao->search(Ticket::class);
        $this->assertEquals(1, count($searchTkt));
        // Check that there is no new fiscal ticket
        $this->assertEquals($fTktCount,
                count($this->dao->search(FiscalTicket::class)));
    }

    /** @depends testSaveTicket
     * Test writing 2 tickets with the same number. */
    public function testSaveTicketOverwrite() {
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $this->api->write($tkt);
        $tkt2 = new Ticket();
        $tkt2->merge($this->sampleTkt(), $this->dao);
        $tkt2->getLines()->get(0)->setProductLabel('test2');
        $exceptionThrown = false;
        try {
            $this->api->write($tkt2);
        } catch (InvalidRecordException $e) {
            $exceptionThrown = true;
            $now = new \DateTime();
            // Check the exception
            $this->assertEquals(InvalidRecordException::CSTR_READ_ONLY,
                    $e->getConstraint());
            $this->assertEquals(Ticket::class, $e->getClass());
            $tktId = $e->getId();
            $this->assertEquals($this->session->getCashRegister()->getId(),
                    $tktId['cashRegister']);
            $this->assertEquals($this->session->getSequence(),
                    $tktId['sequence']);
            $this->assertEquals($tkt->getNumber(), $tktId['number']);
            // Check that the ticket was untouched.
            $searchTkt = $this->dao->search(Ticket::class);
            $this->assertEquals(1, count($searchTkt));
            // Check that the failure fiscal ticket is there.
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $this->assertNotNull($readEOSTkt);
            $this->assertLessThan(3,
                    abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertEquals($fdata['failure'], 'Tickets are read only.');
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
        }
        $this->assertTrue($exceptionThrown);
    }

    /** @depends testSaveTicketOverwrite
     * Test failing the same ticket twice to check that 2 rejects are saved. */
    public function testFailTwice() {
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $this->api->write($tkt);
        $tkt2 = new Ticket();
        $tkt2->merge($this->sampleTkt(), $this->dao);
        $line2 = new TicketLine();
        $line2->setDispOrder(1);
        $line2->setTaxedUnitPrice(22.0);
        $line2->setQuantity(1);
        $line2->setTax($this->tax);
        $line2->setProduct($this->prd);
        $line2->setTaxRate(0.1);
        $line2->setTaxedPrice(22.0);
        $line2->setFinalTaxedPrice(22.0);
        $tkt2->addLine($line2);
        $exceptionThrown = false;
        try {
            $this->api->write($tkt2);
        } catch (InvalidRecordException $e) {
            // this is expected, refail
            try {
                $this->api->write($tkt2);
            } catch (InvalidRecordException $e) {
                $exceptionThrown = true;
                $now = new \DateTime();
                // Check the exception
                $this->assertEquals(InvalidRecordException::CSTR_READ_ONLY,
                        $e->getConstraint());
                $this->assertEquals(Ticket::class, $e->getClass());
                $tktId = $e->getId();
                $this->assertEquals($this->session->getCashRegister()->getId(),
                        $tktId['cashRegister']);
                $this->assertEquals($this->session->getSequence(),
                        $tktId['sequence']);
                $this->assertEquals($tkt->getNumber(), $tktId['number']);
                // Check that the ticket was untouched.
                $searchTkt = $this->dao->search(Ticket::class);
                $this->assertEquals(1, count($searchTkt));
                // Check that the failure fiscal ticket is there.
                $readfTkt = $this->dao->read(FiscalTicket::class, [
                        'type' => FiscalTicket::TYPE_TICKET,
                        'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                        'number' => 1]);
                $readfTkt2 = $this->dao->read(FiscalTicket::class, [
                        'type' => FiscalTicket::TYPE_TICKET,
                        'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                        'number' => 2]);
                $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                        'type' => FiscalTicket::TYPE_TICKET,
                        'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
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
                $this->assertEquals($fdata['failure'], 'Tickets are read only.');
                $fdata2 = json_decode($readfTkt2->getContent(), true);
                $this->assertNotNull($fdata2);
                $this->assertEquals($fdata2['failure'], 'Tickets are read only.');
                $this->assertTrue($readfTkt->checkSignature(null));
                $this->assertTrue($readfTkt2->checkSignature($readfTkt));
                $this->assertTrue($readEOSTkt->checkSignature($readfTkt2));
            }
        }
        $this->assertTrue($exceptionThrown,
                'Expecting InvalidRecordException.');
    }

    /** @depends testSaveTicket
     * Test writing a ticket with invalid data (twice the same line number) */
    public function testSaveScrewedTicket() {
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $line2 = new TicketLine();
        $line2->setDispOrder(1);
        $line2->setTaxedUnitPrice(22.0);
        $line2->setQuantity(1);
        $line2->setTax($this->tax);
        $line2->setProduct($this->prd);
        $line2->setTaxRate(0.1);
        $line2->setTaxedPrice(22.0);
        $line2->setFinalTaxedPrice(22.0);
        $tkt->addLine($line2);
        $exceptionThrown = false;
        try {
            $this->api->write($tkt);
        } catch (UnicityException $e) {
            $exceptionThrown = true;
            $now = new \DateTime();
            // Check the exception
            $this->assertEquals(Ticket::class, $e->getClass());
            $this->assertEquals('lines.dispOrder', $e->getField());
            $this->assertEquals(1, $e->getValue());
            // Check that the ticket was not registered.
            $searchTkt = $this->dao->search(Ticket::class);
            $this->assertEquals(0, count($searchTkt));
            // Check that the failure fiscal ticket is there.
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $this->assertNotNull($readEOSTkt);
            $this->assertLessThan(3,
                    abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertEquals('Error: duplicated line n°1',
                    $fdata['failure']);
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
        }
        $this->assertTrue($exceptionThrown, 'Expecting UnicityException.');
    }

    /** @depends testSaveTicket */
    public function testSaveTicketClosedCash() {
        $sess = $this->dao->read(CashSession::class, $this->session->getId());
        $sess->setCloseDate(new \DateTime('2018-01-01 8:00'));
        $this->dao->write($sess); // as well, editing $this->sesssion doesn't work
        $this->dao->commit();
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $exceptionThrown = false;
        try {
            $this->api->write($tkt);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $now = new \DateTime();
            // Check the exception
            $this->assertEquals(InvalidFieldException::CSTR_OPENED_CASH,
                    $e->getConstraint());
            $this->assertEquals(Ticket::class, $e->getClass());
            $tktId = $e->getId();
            $this->assertEquals($this->session->getCashRegister()->getId(),
                    $tktId['cashRegister']);
            $this->assertEquals($this->session->getSequence(),
                    $tktId['sequence']);
            $this->assertEquals($tkt->getNumber(), $tktId['number']);
            $this->assertEquals('cashRegister&sequence', $e->getField());
            // Check that the ticket is not registered
            $searchTkt = $this->dao->search(Ticket::class);
            $this->assertEquals(0, count($searchTkt));
            // Check that the failure fiscal ticket is there.
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $this->assertNotNull($readEOSTkt);
            $this->assertLessThan(3,
                    abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertEquals($fdata['failure'], 'Tickets must be assigned to an opened cash session.');
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
        }
        $this->assertTrue($exceptionThrown,
                'Expecting InvalidFieldException.');
    }

    /** @depends testSaveTicket */
    public function testSaveTicketCashNotOpened() {
        $session = new CashSession();
        $session->setCashRegister($this->cash);
        $session->setSequence(2);
        $this->dao->write($session);
        $this->dao->commit();
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $tkt->setSequence(2);
        $exceptionThrown = false;
        try {
            $this->api->write($tkt);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $now = new \DateTime();
            // Check the exception
            $this->assertEquals(InvalidFieldException::CSTR_OPENED_CASH,
                    $e->getConstraint());
            $this->assertEquals(Ticket::class, $e->getClass());
            $tktId = $e->getId();
            $this->assertEquals($session->getCashRegister()->getId(),
                    $tktId['cashRegister']);
            $this->assertEquals($session->getSequence(),
                    $tktId['sequence']);
            $this->assertEquals($tkt->getNumber(), $tktId['number']);
            $this->assertEquals('cashRegister&sequence', $e->getField());
            // Check that the ticket is not registered
            $searchTkt = $this->dao->search(Ticket::class);
            $this->assertEquals(0, count($searchTkt));
            // Check that the failure fiscal ticket is there.
            $readfTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 1]);
            $readEOSTkt = $this->dao->read(FiscalTicket::class, [
                    'type' => FiscalTicket::TYPE_TICKET,
                    'sequence' => FiscalTicket::getFailureTicketSequence($tkt),
                    'number' => 0]);
            $this->assertNotNull($readfTkt);
            $this->assertNotNull($readEOSTkt);
            $this->assertLessThan(3,
                    abs($now->getTimestamp() - $readfTkt->getDate()->getTimestamp()));
            $fdata = json_decode($readfTkt->getContent(), true);
            $this->assertNotNull($fdata);
            $this->assertEquals($fdata['failure'], 'Tickets must be assigned to an opened cash session.');
            $this->assertTrue($readfTkt->checkSignature(null));
            $this->assertTrue($readEOSTkt->checkSignature($readfTkt));
        }
        $this->assertTrue($exceptionThrown,
                'Expecting InvalidFieldException.');
    }

    /** @depends testSaveTicket */
    public function testCustBalanceTicket() {
        $cust = new Customer();
        $cust->setDispName('Customer');
        $this->dao->write($cust);
        $this->dao->commit();
        $tkt = new Ticket();
        $tkt->setCashRegister($this->session->getCashRegister());
        $tkt->setSequence($this->session->getSequence());
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 8:05'));
        $tkt->setUser($this->user);
        $tkt->setTaxedPrice(11.0);
        $tkt->setFinalPrice(10.0); $tkt->setFinalTaxedPrice(11.0);
        $tkt->setCustomer($cust);
        $tkt->setCustBalance(10.0);
        $this->api->write($tkt);
        $readCust = $this->dao->readSnapshot(Customer::class, $cust->getId());
        $this->assertNotNull($readCust);
        $this->assertEquals(10.0, $readCust->getBalance());
        // Redo to check addition
        $tkt2 = new Ticket();
        $tkt2->setCashRegister($this->session->getCashRegister());
        $tkt2->setSequence($this->session->getSequence());
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 8:05'));
        $tkt2->setUser($this->user);
        $tkt2->setTaxedPrice(5.5);
        $tkt2->setFinalPrice(5.0); $tkt2->setFinalTaxedPrice(11.0);
        $tkt2->setCustomer($cust);
        $tkt2->setCustBalance(5.0);
        $this->api->write($tkt2);
        $readCust = $this->dao->readSnapshot(Customer::class, $cust->getId());
        $this->assertNotNull($readCust);
        $this->assertEquals(15.0, $readCust->getBalance());
    }

    /** @depends testSaveTicket */
    public function testTopCustomers() {
        $cTop = new Customer();
        $cTop->setDispName('Top');
        $this->dao->write($cTop);
        $cTop2 = new Customer();
        $cTop2->setDispName('Top2');
        $this->dao->write($cTop2);
        $cTopNull = new Customer();
        $cTopNull->setDispName('Not customer');
        $this->dao->write($cTopNull);
        $this->dao->commit();
        // Add tickets to Top
        $i = 1;
        for (; $i < 3; $i++) {
            $tkt = new Ticket();
            $tkt->setCashRegister($this->session->getCashRegister());
            $tkt->setSequence($this->session->getSequence());
            $tkt->setNumber($i);
            $tkt->setDate(new \DateTime('2018-01-01 8:05'));
            $tkt->setUser($this->user);
            $tkt->setTaxedPrice(11.0);
            $tkt->setFinalPrice(10.0); $tkt->setFinalTaxedPrice(11.0);
            $tkt->setCustomer($cTop);
            $this->api->write($tkt);
        }
        // Add tickets to Top2
        for (; $i < 4; $i++) {
            $tkt = new Ticket();
            $tkt->setCashRegister($this->session->getCashRegister());
            $tkt->setSequence($this->session->getSequence());
            $tkt->setNumber($i);
            $tkt->setDate(new \DateTime('2018-01-01 8:05'));
            $tkt->setUser($this->user);
            $tkt->setTaxedPrice(11.0);
            $tkt->setFinalPrice(10.0); $tkt->setFinalTaxedPrice(11.0);
            $tkt->setCustomer($cTop2);
            $this->api->write($tkt);
        }
        // Check that Top and Top2 are returned and not TopNull
        $custAPI = new CustomerAPI($this->dao);
        $topIds = $custAPI->getTopIds(3);
        $this->assertEquals(2, count($topIds));
        $this->assertEquals($cTop->getId(), $topIds[0]);
        $this->assertEquals($cTop2->getId(), $topIds[1]);
        $top = $custAPI->getTop(3);
        $this->assertEquals(2, count($top));
        $this->assertEquals($cTop->getId(), $top[0]->getId());
        $this->assertEquals($cTop2->getId(), $top[1]->getId());
    }

    /** @depends testSaveTicket */
    public function testUpdateNextTicketId() {
        // Write ticket number 1, expect next = 2
        $tkt = new Ticket();
        $tkt->setCashRegister($this->session->getCashRegister());
        $tkt->setSequence($this->session->getSequence());
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 8:05'));
        $tkt->setUser($this->user);
        $tkt->setTaxedPrice(11.0);
        $tkt->setFinalPrice(10.0); $tkt->setFinalTaxedPrice(11.0);
        $this->api->write($tkt);
        $read = $this->dao->readSnapshot(CashRegister::class,
                $tkt->getCashRegister()->getId());
        $this->assertEquals(2, $read->getNextTicketId());
        // Write ticket number 5, expect next = 6
        $tkt = new Ticket();
        $tkt->setCashRegister($this->session->getCashRegister());
        $tkt->setSequence($this->session->getSequence());
        $tkt->setNumber(5);
        $tkt->setDate(new \DateTime('2018-01-01 8:05'));
        $tkt->setUser($this->user);
        $tkt->setTaxedPrice(11.0);
        $tkt->setFinalPrice(10.0); $tkt->setFinalTaxedPrice(11.0);
        $this->api->write($tkt);
        $read = $this->dao->readSnapshot(CashRegister::class,
                $tkt->getCashRegister()->getId());
        $this->assertEquals(6, $read->getNextTicketId());
        // Write ticket number 2, expect next still = 6
        $tkt = new Ticket();
        $tkt->setCashRegister($this->session->getCashRegister());
        $tkt->setSequence($this->session->getSequence());
        $tkt->setNumber(2);
        $tkt->setDate(new \DateTime('2018-01-01 8:05'));
        $tkt->setUser($this->user);
        $tkt->setTaxedPrice(11.0);
        $tkt->setFinalPrice(10.0); $tkt->setFinalTaxedPrice(11.0);
        $this->api->write($tkt);
        $read = $this->dao->readSnapshot(CashRegister::class,
                $tkt->getCashRegister()->getId());
        $this->assertEquals(6, $read->getNextTicketId());
    }

    /** @depends testSaveTicket
     * Save tickets in a different order, check that the fiscal tickets
     * are registered in the order they came. */
    public function testSaveTicketUnordered() {
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $tkt2 = new Ticket();
        $tkt2->merge($this->sampleTkt(), $this->dao);
        $tkt2->setNumber(2);
        $this->api->write($tkt2);
        $this->api->write($tkt);
        // Look at the fiscal tickets
        $searchTkt = $this->dao->search(FiscalTicket::class, null, null, null,
                'number');
        $this->assertEquals(3, count($searchTkt));
        $eos = $searchTkt[0];
        $ft1 = $searchTkt[1];
        $ft2 = $searchTkt[2];
        $this->assertEquals(1, $ft1->getNumber());
        $this->assertEquals(2, $ft2->getNumber());
        $this->assertTrue($ft1->checkSignature(null));
        $this->assertTrue($ft2->checkSignature($ft1));
        $this->assertTrue($eos->checkSignature($ft2));
        $contentFT1 = json_decode($ft1->getContent(), true);
        $this->assertEquals(2, $contentFT1['number']);
        $contentFT2 = json_decode($ft2->getContent(), true);
        $this->assertEquals(1, $contentFT2['number']);
    }

    /** @depends testSaveTicket */
    public function testRegisterFiscalConcurrent() {
        // Say this is the concurrent one: register one fiscal ticket
        $tkt = new Ticket();
        $tkt->merge($this->sampleTkt(), $this->dao);
        $this->api->write($tkt);
        // Try to save fiscal ticket number 1
        // (first write tested in testSaveTicket)
        $type = FiscalTicket::TYPE_TICKET;
        $sequence = FiscalTicket::getTicketSequence($tkt);
        $content = 'concurrent';
        $fTicket = new FiscalTicket();
        $fTicket->setType($type);
        $fTicket->setSequence($sequence);
        $fTicket->setNumber(1);
        $fTicket->setDate(new \DateTime());
        $fTicket->setContent(json_encode($content));
        $fTicket->sign(null);
        $this->dao->write($fTicket);
        $this->api->updateEOSTicketProxy($fTicket);
        $exceptionThrown = false;
        try {
            $this->dao->commit();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
        // Second pass, copied code from the while loop in TicketAPI
        $fAPI = new \Pasteque\Server\API\FiscalAPI($this->dao);
        $fTicket = new FiscalTicket();
        $prevFTicket = $fAPI->getLastFiscalTicket($type, $sequence);
        if ($prevFTicket !== null) {
            $fTicket->setNumber($prevFTicket->getNumber() + 1);
        }
        $fTicket->setType($type);
        $fTicket->setSequence($sequence);
        $fTicket->setDate(new \DateTime());
        $fTicket->setContent(json_encode($content));
        $fTicket->sign($prevFTicket);
        $this->dao->write($fTicket);
        $this->api->updateEOSTicketProxy($fTicket);
        $registered = false;
        $this->dao->commit(); // should not throw an exception
        $registered = true;
        $this->assertTrue($registered);
        $this->assertEquals(2, $fTicket->getNumber());
    }
}
