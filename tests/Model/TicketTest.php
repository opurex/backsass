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
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\Model\TicketLine;
use \Pasteque\Server\Model\TicketPayment;
use \Pasteque\Server\Model\TicketTax;
use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");
require_once(dirname(dirname(__FILE__)) . "/common_ticket.php");

/** Test for CashSession and its subclasses CashSessionCat, CashSessionTax... */
class TicketTest extends TestCase
{
    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
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
        foreach ([TicketPayment::class, TicketTax::class,
                TicketLine::class, Ticket::class,
                CashSession::class, CashRegister::class, User::class,
                Role::class, Tax::class, PaymentMode::class, Currency::class]
                as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    /** Test the old format from Pasteque Desktop 8.0-8.6 which used hardcoded
     * payment modes. */
    public function testPaymentLoadDesktopOld() {
        $struct = ['amount' => 10, 'currencyAmount' => 12,
                'desktop' => true, 'dispOrder' => 0,
                'type' => $this->paymentMode->getReference(),
                'currency' => $this->currency->getId()];
        $ticket = new Ticket();
        $tktPayment = TicketPayment::loadOrCreate($struct, $ticket,
                $this->dao);
        $tktPayment->merge($struct, $this->dao);
        $this->assertNotNull($tktPayment->getPaymentMode());
        $this->assertEquals($this->paymentMode->getReference(),
                $tktPayment->getPaymentMode()->getReference());
    }

    public function testEquals() {
        $struct = getBaseTicket($this->cash, $this->session, 1, $this->user);
        $struct['lines'][] = getBaseLine($this->tax, 1, 'product', 11.0);
        $struct['tax'][] = getBaseTax($this->tax, 10.0, 11.0);
        $struct['payments'][] = getBasePayment($this->paymentMode, $this->currency,
                1, 11.0);
        $tkt1 = new Ticket();
        $tkt1->merge($struct, $this->dao);
        $tkt2 = new Ticket();
        $tkt2->merge($struct, $this->dao);
        $this->assertTrue($tkt1->equals($tkt2));
    }

    public function testEqualsIgnoresId() {
        $struct = getBaseTicket($this->cash, $this->session, 1, $this->user);
        $struct['lines'][] = getBaseLine($this->tax, 1, 'product', 11.0);
        $struct['tax'][] = getBaseTax($this->tax, 10.0, 11.0);
        $struct['payments'][] = getBasePayment($this->paymentMode, $this->currency,
                1, 11.0);
        $tkt1 = new Ticket();
        $tkt1->merge($struct, $this->dao);
        $tkt2 = new Ticket();
        $tkt2->merge($struct, $this->dao);
        $this->dao->write($tkt1);
        $this->dao->commit();
        $this->assertTrue($tkt1->equals($tkt2));
    }

    /** @depends testEquals */
    public function testUnequalsNumber() {
        $struct = getBaseTicket($this->cash, $this->session, 1, $this->user);
        $struct['lines'][] = getBaseLine($this->tax, 1, 'product', 11.0);
        $struct['tax'][] = getBaseTax($this->tax, 10.0, 11.0);
        $struct['payments'][] = getBasePayment($this->paymentMode,
                $this->currency, 1, 11.0);
        $tkt1 = new Ticket();
        $tkt1->merge($struct, $this->dao);
        $struct['number'] = 2;
        $tkt2 = new Ticket();
        $tkt2->merge($struct, $this->dao);
        $this->assertFalse($tkt1->equals($tkt2));
    }

    /** @depends testEquals */
    public function testUnequalsDate() {
        $struct = getBaseTicket($this->cash, $this->session, 1, $this->user);
        $struct['lines'][] = getBaseLine($this->tax, 1, 'product', 11.0);
        $struct['tax'][] = getBaseTax($this->tax, 10.0, 11.0);
        $struct['payments'][] = getBasePayment($this->paymentMode,
                $this->currency, 1, 11.0);
        $tkt1 = new Ticket();
        $tkt1->merge($struct, $this->dao);
        $struct['date'] += 2000;
        $tkt2 = new Ticket();
        $tkt2->merge($struct, $this->dao);
        $this->assertFalse($tkt1->equals($tkt2));
    }

    /** @depends testEquals */
    public function testUnequalsLine() {
        $struct = getBaseTicket($this->cash, $this->session, 1, $this->user);
        $struct['lines'][] = getBaseLine($this->tax, 1, 'product', 11.0);
        $struct['tax'][] = getBaseTax($this->tax, 10.0, 11.0);
        $struct['payments'][] = getBasePayment($this->paymentMode,
                $this->currency, 1, 11.0);
        $tkt1 = new Ticket();
        $tkt1->merge($struct, $this->dao);
        $struct['lines'][0]['productLabel'] = 'other product';
        $tkt2 = new Ticket();
        $tkt2->merge($struct, $this->dao);
        $this->assertFalse($tkt1->equals($tkt2));
    }
}
