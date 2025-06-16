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

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\CashSessionCat;
use \Pasteque\Server\Model\CashSessionCatTax;
use \Pasteque\Server\Model\CashSessionPayment;
use \Pasteque\Server\Model\CashSessionTax;
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
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . '/common_load.php');
require_once(dirname(dirname(__FILE__)) . '/common_session.php');
require_once(dirname(dirname(__FILE__)) . '/common_ticket.php');

class HttpCashSessionTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $tax;
    private $cat;
    private $prd;
    private $cash;
    private $pm;
    private $curr;
    private $role;
    private $user;

    public static function setUpBeforeClass(): void {
        static::$token = obtainToken();
    }

    public static function tearDownAfterClass(): void {
    }    

    protected function setUp(): void {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token]);
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
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
        $this->dao->write($this->session);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([FiscalTicket::class, TicketPayment::class, TicketTax::class,
                        TicketLine::class, Ticket::class, Customer::class,
                        CashSessionTax::class, CashSessionCatTax::class,
                        CashSessionCat::class, CashSessionPayment::class,
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

    public function testGet() {
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashsession/%d/%d',
                        $this->cash->getId(), 1)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals($this->cash->getId(), $jsResp['cashRegister']);
        $this->assertEquals(1, $jsResp['sequence']);
    }

    public function testGetNone() {
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashsession/%d/%d',
                        $this->cash->getId(), 2)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertEquals(null, $jsResp);
    }

    public function testGetNotFound() {
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/cashsession/%d/%d',
                        $this->cash->getId() + 1, 1)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals($this->cash->getId() + 1, $jsResp['key']['id']);
    }


    public function testOpen() {
        $structSess = $this->session->toStruct();
        $structSess['openDate'] = DateUtils::toTimestamp(new \DateTime('2018-01-01 10:00'));
        $structSess['openCash'] = 200.0;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $snap = $this->dao->readSnapshot(CashSession::class, $this->session->getId());
        $this->assertTrue(DateUtils::equals($structSess['openDate'], $snap->getOpenDate()));
        assertSessionModelEqStruct($snap, $structSess, $this);
        assertSessionModelEqStruct($snap, $data, $this);
    }

    /** @depends testOpen */
    public function testCloseEmpty() {
        $structSess = $this->session->toStruct();
        $structSess['openDate'] = DateUtils::toTimestamp(new \DateTime('2018-01-01 10:00'));
        $structSess['openCash'] = 200.0;
        $structSess['closeCash'] = 200.0;
        $structSess['expectedCash'] = 200.0;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        curl_exec($this->curl);
        $structSess['closeDate'] = DateUtils::toTimestamp(new \DateTime('2018-01-01 10:02'));
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $snap = $this->dao->readSnapshot(CashSession::class, $this->session->getId());
        $this->assertTrue(DateUtils::equals($structSess['openDate'], $snap->getOpenDate()));
        assertSessionModelEqStruct($snap, $structSess, $this);
        assertSessionModelEqStruct($snap, $data, $this);
    }

    public function testSummary() {
        // Open the session and add a ticket
        $this->session->setOpenDate(new \DateTime('2018-01-01 10:00'));
        $this->session->setOpenCash(200.0);
        $this->dao->write($this->session);
        $this->dao->commit();
        $tkt = ticketNew($this->session->getCashRegister(), $this->session->getSequence(), 1, '2018-01-01 10:08', $this->user);
        ticketAddLine($tkt, $this->prd, 1);
        ticketAddPayment($tkt, $this->pm, $this->curr, 1.1);
        ticketFinalize($tkt);
        $this->dao->write($tkt);
        $this->dao->commit();
        // Get summary and check it
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cashsession/summary/' . $this->session->getCashRegister()->getId() . '/' . $this->session->getSequence()));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        // Check basic data
        $this->assertEquals($this->session->getCashRegister()->getId(), $data['cashRegister']);
        $this->assertEquals($this->session->getSequence(), $data['sequence']);
        $this->assertEquals(1, $data['ticketCount']);
        $this->assertEquals(null, $data['custCount']);
        $this->assertEquals(1, $data['paymentCount']);
        $this->assertEquals(1.0, $data['cs']);
        // Check payments
        $this->assertEquals(1, count($data['payments']));
        $pmt = $data['payments'][0];
        $this->assertEquals($this->pm->getReference(), $pmt['type']);
        $this->assertEquals($this->curr->getId(), $pmt['currency']);
        $this->assertEquals(1.1, $pmt['amount']);
        $this->assertEquals(1.1, $pmt['currencyAmount']);
        // Check taxes
        $this->assertEquals(1, count($data['taxes']));
        $tax = $data['taxes'][0];
        $this->assertEquals($this->tax->getId(), $tax['tax']);
        $this->assertEquals(1.0, $tax['base']);
        $this->assertEquals(0.1, $tax['amount']);
        // Check categories
        $this->assertEquals(1, count($data['catSales']));
        $cat = $data['catSales'][0];
        $this->assertEquals($this->cat->getId(), $cat['category']);
        $this->assertEquals(1.0, $cat['amount']);
        // Check category sales
        $this->assertEquals(1, count($data['catTaxes']));
        $catTax = $data['catTaxes'][0];
        $this->assertEquals($this->tax->getId(), $catTax['tax']);
        $this->assertEquals($this->cat->getReference(), $catTax['reference']);
        $this->assertEquals($this->cat->getLabel(), $catTax['label']);
        $this->assertEquals(1.0, $catTax['base']);
        $this->assertEquals(0.1, $catTax['amount']);
        // Check customer's balances
        $this->assertEquals(0, count($data['custBalances']));
    }

    /** @depends testCloseEmpty */
    public function testClose() {
        // Open the session and add a ticket
        $this->session->setOpenDate(new \DateTime('2018-01-01 10:00'));
        $this->session->setOpenCash(200.0);
        $this->dao->write($this->session);
        $this->dao->commit();
        $tkt = ticketNew($this->session->getCashRegister(), $this->session->getSequence(), 1, '2018-01-01 10:08', $this->user);
        ticketAddLine($tkt, $this->prd, 1);
        ticketAddPayment($tkt, $this->pm, $this->curr, 1.1);
        ticketFinalize($tkt);
        $this->dao->write($tkt);
        $this->dao->commit();
        $structSess = $this->session->toStruct();
        $structSess['closeDate'] = '2018-01-01 11:00';
        $structSess['closeCash'] = 201.1;
        $structSess['expectedCash'] = 201.1;
        $structSess['closeType'] = CashSession::CLOSE_SIMPLE;
        $structSess['cs'] = 1.1;
        $structSess['csPeriod'] = 1.1;
        $structSess['csFYear'] = 1.1;
        $structSess['csPerpetual'] = 1.1;
        $structSess['payments'] = [
            ['paymentMode' => $this->pm->getId(), 'currency' => $this->curr->getId(),
             'amount' => 1.1, 'currencyAmount' => 1.1]
        ];
        $structSess['taxes'] = [
            ['tax' => $this->tax->getId(), 'taxRate' => $this->tax->getRate(),
             'base' => 1.0, 'amount' => 0.1,
             'basePeriod' => 1.0, 'amountPeriod' => 0.1,
             'baseFYear' => 1.0, 'amountFYear' => 0.1]
        ];
        $structSess['catSales'] = [
            ['reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'amount' => 1.0]
        ];
        $structSess['catTaxes'] = [
            ['tax' => $this->tax->getId(), 'reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'base' => 1.0, 'amount' => 0.1]
        ];
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $snap = $this->dao->readSnapshot(CashSession::class, $this->session->getId());
        assertSessionModelEqStruct($snap, $structSess, $this);
        assertSessionModelEqStruct($snap, $data, $this);
    }

    public function testCloseOldDesktop() {
        // Open the session and add a ticket
        $this->session->setOpenDate(new \DateTime('2018-01-01 10:00'));
        $this->session->setOpenCash(200.0);
        $this->dao->write($this->session);
        $this->dao->commit();
        $tkt = ticketNew($this->session->getCashRegister(), $this->session->getSequence(), 1, '2018-01-01 10:08', $this->user);
        ticketAddLine($tkt, $this->prd, 1);
        ticketAddPayment($tkt, $this->pm, $this->curr, 1.1);
        ticketFinalize($tkt);
        $this->dao->write($tkt);
        $this->dao->commit();
        $structSess = $this->session->toStruct();
        $structSess['closeDate'] = '2018-01-01 11:00';
        $structSess['closeCash'] = 201.1;
        $structSess['expectedCash'] = 201.1;
        $structSess['closeType'] = CashSession::CLOSE_SIMPLE;
        $structSess['cs'] = 1.1;
        $structSess['csPeriod'] = 1.1;
        $structSess['csFYear'] = 1.1;
        $structSess['csPerpetual'] = 1.1;
        $structSess['payments'] = [
            ['desktop' => true, 'type' => $this->pm->getReference(),
             'currency' => $this->curr->getId(),
             'amount' => 1.1, 'currencyAmount' => 1.1]
        ];
        $structSess['taxes'] = [
            ['tax' => $this->tax->getId(), 'taxRate' => $this->tax->getRate(),
             'base' => 1.0, 'amount' => 0.1,
             'basePeriod' => 1.0, 'amountPeriod' => 0.1,
             'baseFYear' => 1.0, 'amountFYear' => 0.1]
        ];
        $structSess['catSales'] = [
            ['reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'amount' => 1.0]
        ];
        $structSess['catTaxes'] = [
            ['tax' => $this->tax->getId(), 'reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'base' => 1.0, 'amount' => 0.1]
        ];
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $snap = $this->dao->readSnapshot(CashSession::class, $this->session->getId());
        $structSess['payments'][0]['paymentMode'] = $this->pm->getId();
        assertSessionModelEqStruct($snap, $structSess, $this);
        assertSessionModelEqStruct($snap, $data, $this);
    }

    /** @depends testClose */
    public function testCloseNoPerpetualFirst() {
        // Open the session and add a ticket
        $this->session->setOpenDate(new \DateTime('2018-01-01 10:00'));
        $this->session->setOpenCash(200.0);
        $this->dao->write($this->session);
        $this->dao->commit();
        $tkt = ticketNew($this->session->getCashRegister(), $this->session->getSequence(), 1, '2018-01-01 10:08', $this->user);
        ticketAddLine($tkt, $this->prd, 1);
        ticketAddPayment($tkt, $this->pm, $this->curr, 1.1);
        ticketFinalize($tkt);
        $this->dao->write($tkt);
        $this->dao->commit();
        $structSess = $this->session->toStruct();
        $structSess['closeDate'] = '2018-01-01 11:00';
        $structSess['closeCash'] = 201.1;
        $structSess['expectedCash'] = 201.1;
        $structSess['closeType'] = CashSession::CLOSE_SIMPLE;
        $structSess['cs'] = 1.1;
        $structSess['csPeriod'] = 1.1;
        $structSess['csFYear'] = 1.1;
        $structSess['payments'] = [
            ['paymentMode' => $this->pm->getId(), 'currency' => $this->curr->getId(),
             'amount' => 1.1, 'currencyAmount' => 1.1]
        ];
        $structSess['taxes'] = [
            ['tax' => $this->tax->getId(), 'taxRate' => $this->tax->getRate(),
             'base' => 1.0, 'amount' => 0.1,
             'basePeriod' => 1.0, 'amountPeriod' => 0.1,
             'baseFYear' => 1.0, 'amountFYear' => 0.1]
        ];
        $structSess['catSales'] = [
            ['reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'amount' => 1.0]
        ];
        $structSess['catTaxes'] = [
            ['tax' => $this->tax->getId(), 'reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'base' => 1.0, 'amount' => 0.1]
        ];
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $snap = $this->dao->readSnapshot(CashSession::class, $this->session->getId());
        $structSess['csPerpetual'] = 1.1; // set to check equality
        assertSessionModelEqStruct($snap, $structSess, $this);
        assertSessionModelEqStruct($snap, $data, $this);
    }

    /** @depends testClose */
    public function testCloseNoPerpetualNext() {
        // Open the session and add a ticket
        $this->session->setOpenDate(new \DateTime('2018-01-01 10:00'));
        $this->session->setOpenCash(200.0);
        $this->session->setCS(10.0);
        $this->session->setCSPeriod(10.0);
        $this->session->setCSFYear(10.0);
        $this->session->setCSPerpetual(10.0);
        $this->session->setCloseDate(new \DateTime('2018-01-01 13:00'));
        $this->dao->write($this->session);
        $this->dao->commit();
        $session2 = new CashSession();
        $session2->setCashRegister($this->cash);
        $session2->setSequence(2);
        $session2->setOpenDate(new \DateTime('2018-01-02 11:30'));
        $session2->setCSPeriod(10.0);
        $session2->setCSFYear(10.0);
        $session2->setCSPerpetual(10.0);
        $this->dao->write($session2);
        $this->dao->commit();
        $tkt = ticketNew($this->session->getCashRegister(),
                $session2->getSequence(), 1, '2018-01-02 12:08', $this->user);
        ticketAddLine($tkt, $this->prd, 1);
        ticketAddPayment($tkt, $this->pm, $this->curr, 1.1);
        ticketFinalize($tkt);
        $this->dao->write($tkt);
        $this->dao->commit();
        $structSess = $session2->toStruct();
        $structSess['closeDate'] = '2018-01-02 15:00';
        $structSess['closeCash'] = 201.1;
        $structSess['expectedCash'] = 201.1;
        $structSess['closeType'] = CashSession::CLOSE_SIMPLE;
        $structSess['cs'] = 1.1;
        $structSess['csPeriod'] = 11.1;
        $structSess['csFYear'] = 11.1;
        unset($structSess['csPerpetual']);
        $structSess['payments'] = [
            ['paymentMode' => $this->pm->getId(), 'currency' => $this->curr->getId(),
             'amount' => 1.1, 'currencyAmount' => 1.1]
        ];
        $structSess['taxes'] = [
            ['tax' => $this->tax->getId(), 'taxRate' => $this->tax->getRate(),
             'base' => 1.0, 'amount' => 0.1,
             'basePeriod' => 1.0, 'amountPeriod' => 0.1,
             'baseFYear' => 1.0, 'amountFYear' => 0.1]
        ];
        $structSess['catSales'] = [
            ['reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'amount' => 1.0]
        ];
        $structSess['catTaxes'] = [
            ['tax' => $this->tax->getId(), 'reference' => $this->cat->getReference(), 'label' => $this->cat->getLabel(), 'base' => 1.0, 'amount' => 0.1]
        ];
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $snap = $this->dao->readSnapshot(CashSession::class, $session2->getId());
        $structSess['csPerpetual'] = 11.1; // set to check equality
        assertSessionModelEqStruct($snap, $structSess, $this);
        assertSessionModelEqStruct($snap, $data, $this);
    }

    /** @depends testOpen
     * Send malformed data. */
    public function TestSyntaxError() {
        $structSess = $this->session->toStruct();
        $structSess['openDate'] = DateUtils::toTimestamp(new \DateTime('2018-01-01 10:00'));
        $structSess['openCash'] = 200.0;
        $data = substr(json_encode($structSess), 1);
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . $data);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        // Check that the failure is registered
        $ftkts = $this->dao->search(FiscalTicket::class,
                new DAOCondition('sequence', '=', FiscalTicket::getGeneralFailureSequence()),
                null, null, '-number');
        $this->assertEquals(2, count($ftkts));
        $ftkt = $ftkts[0];
        $fail = json_decode($ftkt->getContent(), true);
        $this->assertEquals($data, $fail['input']);
        $this->assertEquals('Unable to parse input data', $fail['failure']);
    }

    /** @depends testOpen
     * Send session with invalid data. */
    public function testInvalidData() {
        $structSess = $this->session->toStruct();
        $structSess['openDate'] = DateUtils::toTimestamp(new \DateTime('2018-01-01 10:00'));
        $structSess['openCash'] = 'NaN';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/cash'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'session=' . json_encode($structSess));
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $err = json_decode($resp, true);
        $this->assertNotNull($err, $resp);
        $this->assertEquals('InvalidField', $err['error']);
        $this->assertEquals(InvalidFieldException::CSTR_FLOAT,
                $err['constraint']);
        $this->assertEquals('openCash', $err['field']);
        // Check that the fiscal ticket is registered
        $ftkts = $this->dao->search(FiscalTicket::class,
                new DAOCondition('sequence', '=', FiscalTicket::getGeneralFailureSequence()), null, null, '-number');
        $this->assertEquals(2, count($ftkts));
        $ftkt = $ftkts[0];
        $fail = json_decode($ftkt->getContent(), true);
        $this->assertNotNull($fail);
        $inputFisc = $fail['input'];
        $this->assertEquals($structSess['sequence'], $inputFisc['sequence']);
        $this->assertEquals('NaN', $inputFisc['openCash']);
        $errFisc = $fail['failure'];
        $this->assertEquals('InvalidField', $errFisc['error']);
        $this->assertEquals(InvalidFieldException::CSTR_FLOAT,
                $errFisc['constraint']);
        $this->assertEquals('openCash', $errFisc['field']);
   }


}
