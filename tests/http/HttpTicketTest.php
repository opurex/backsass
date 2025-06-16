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
use \Pasteque\Server\Exception\InvalidRecordException;
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
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");
require_once(dirname(dirname(__FILE__)) . "/common_ticket.php");

class HttpTicketTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $tax;
    private $cat;
    private $prd;
    private $cash;
    private $session;
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
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/ticket'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
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
        $this->session->setOpenDate(new \DateTime('2018-01-01 8:00'));
        $this->dao->write($this->session);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
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

    private function assertTicketModelEqStruct($model, $struct) {
        // Proxy for common_ticket
        assertTicketModelEqStruct($model, $struct, $this);
    }

    public function testSearch() {
        $tktStruct = $this->getBaseTicket(1);
        $tktStruct['date'] = DateUtils::readDate('2018-01-02 10:00');
        $tkt = new Ticket();
        $tkt->merge($tktStruct, $this->dao);
        $this->dao->write($tkt);
        $tkt2Struct = $this->getBaseTicket(2);
        $tkt2Struct['date'] = DateUtils::readDate('2018-01-03 11:00');
        $tkt2 = new Ticket();
        $tkt2->merge($tkt2Struct, $this->dao);
        $this->dao->write($tkt2);
        $this->dao->commit();
        $queryParams = http_build_query([
                'cashRegister' => $this->cash->getId(),
                'dateStart' => '2018-01-03',
                'dateStop' => '2018-01-04',
        ]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals(1, count($jsResp));
        $this->assertEquals(2, $jsResp[0]['number']);
        $queryParams = http_build_query([
                'cashRegister' => $this->cash->getId(),
                'offset' => 1
        ]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals(1, count($jsResp));
        $this->assertEquals(1, $jsResp[0]['number']);
    }

    public function testSearchCount() {
        $tktStruct = $this->getBaseTicket(1);
        $tktStruct['date'] = DateUtils::readDate('2018-01-02 10:00');
        $tkt = new Ticket();
        $tkt->merge($tktStruct, $this->dao);
        $this->dao->write($tkt);
        $tkt2Struct = $this->getBaseTicket(2);
        $tkt2Struct['date'] = DateUtils::readDate('2018-01-03 11:00');
        $tkt2 = new Ticket();
        $tkt2->merge($tkt2Struct, $this->dao);
        $this->dao->write($tkt2, $this->dao);
        $this->dao->write($tkt2);
        $this->dao->commit();
        $queryParams = http_build_query([
                'cashRegister' => $this->cash->getId(),
                'dateStart' => '2018-01-03',
                'dateStop' => '2018-01-04',
                'count' => true
        ]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals(1, $jsResp);
    }

    public function testSearchInvalid() {
        $queryParams = http_build_query(['dateStart' => 'notadate']);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('InvalidDate', $jsResp['constraint']);
        $this->assertEquals('dateStart', $jsResp['field']);
        $this->assertEquals(null, $jsResp['key']);
        $this->assertEquals('notadate', $jsResp['value']);
        $queryParams = http_build_query(['dateStop' => 'notadate']);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('InvalidDate', $jsResp['constraint']);
        $this->assertEquals('dateStop', $jsResp['field']);
        $this->assertEquals(null, $jsResp['key']);
        $this->assertEquals('notadate', $jsResp['value']);
    }

    public function testSearchNotFound() {
        $queryParams = http_build_query(
                ['cashRegister' => $this->cash->getId() + 1]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals($this->cash->getId() + 1, $jsResp['key']['id']);
        $queryParams = http_build_query(
                ['customer' => 1]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(Customer::class, $jsResp['class']);
        $this->assertEquals(1, $jsResp['key']['id']);
        $queryParams = http_build_query(
                ['user' => $this->user->getId() + 1]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/search?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(User::class, $jsResp['class']);
        $this->assertEquals($this->user->getId() + 1, $jsResp['key']['id']);
    }

    public function testSearchSession() {
        $tktStruct = $this->getBaseTicket(1);
        $tktStruct['date'] = DateUtils::readDate('2018-01-02 8:00');
        $tkt = new Ticket();
        $tkt->merge($tktStruct, $this->dao);
        $this->dao->write($tkt);
        $session = new CashSession();
        $session->setCashRegister($this->cash);
        $session->setSequence(2);
        $session->setOpenDate(new \DateTime('2018-01-03 8:00'));
        $this->dao->write($session);
        $tkt2Struct = $this->getBaseTicket(2);
        $tkt2Struct['date'] = DateUtils::readDate($tkt2Struct['date']);
        $tkt2Struct['sequence'] = 2;
        $tkt2 = new Ticket();
        $tkt2->merge($tkt2Struct, $this->dao);
        $this->dao->write($tkt2);
        $this->dao->commit();
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/session/%d/%d',
                        $this->cash->getId(), 2)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals(1, count($jsResp));
        $this->assertEquals(2, $jsResp[0]['number']);
    }

    public function testSearchSessionCount() {
        $tktStruct = $this->getBaseTicket(1);
        $tktStruct['date'] = DateUtils::readDate('2018-01-02 8:00');
        $tkt = new Ticket();
        $tkt->merge($tktStruct, $this->dao);
        $this->dao->write($tkt);
        $session = new CashSession();
        $session->setCashRegister($this->cash);
        $session->setSequence(2);
        $session->setOpenDate(new \DateTime('2018-01-03 8:00'));
        $this->dao->write($session);
        $tkt2Struct = $this->getBaseTicket(2);
        $tkt2Struct['date'] = DateUtils::readDate($tkt2Struct['date']);
        $tkt2Struct['sequence'] = 2;
        $tkt2 = new Ticket();
        $tkt2->merge($tkt2Struct, $this->dao);
        $this->dao->write($tkt2);
        $this->dao->commit();
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/session/%d/%d?count=1',
                        $this->cash->getId(), 2)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals(1, $jsResp);
    }

    public function testSearchSessionNotFound() {
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/session/%d/%d',
                        $this->cash->getId() + 1, 1)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(CashRegister::class, $jsResp['class']);
        $this->assertEquals($this->cash->getId() + 1, $jsResp['key']['id']);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/ticket/session/%d/%d',
                        $this->cash->getId(), 3)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(404, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('RecordNotFound', $jsResp['error']);
        $this->assertEquals(CashSession::class, $jsResp['class']);
        $this->assertEquals($this->cash->getId(),
                $jsResp['key']['cashRegister']);
        $this->assertEquals(3, $jsResp['key']['sequence']);
    }


    public function testEmpty() {
        $input = [];
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode($input));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(0, count($data['successes']), $resp);
        $this->assertEquals(0, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    /** Write a single regular ticket. */
    public function testSingle() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(1, count($data['successes']), $resp);
        $s = $data['successes'][0];
        $this->assertEquals($this->cash->getId(), $s['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s['sequence'], $resp);
        $this->assertEquals(1, $s['number'], $resp);
        $snap = readTicketSnapshot($this->cash, $s['sequence'], $s['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap, $tkt);
        $this->assertEquals(0, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    public function testSingleNoPrdLabel() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, null, 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(1, count($data['successes']), $resp);
        $s = $data['successes'][0];
        $this->assertEquals($this->cash->getId(), $s['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s['sequence'], $resp);
        $this->assertEquals(1, $s['number'], $resp);
        $snap = readTicketSnapshot($this->cash, $s['sequence'], $s['number'], $this->dao);
        $tkt['lines'][0]['productLabel'] = ''; // Expected value
        $this->assertTicketModelEqStruct($snap, $tkt);
        $this->assertEquals(0, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    /** @depends testSingle */
    public function testSingleTwice() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        $daoTkt = new Ticket();
        $daoTkt->merge($tkt, $this->dao);
        $this->dao->write($daoTkt);
        $this->dao->commit();
        $this->dao->close();
        sleep(10);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(1, count($data['successes']), $resp);
        $s = $data['successes'][0];
        $this->assertEquals($this->cash->getId(), $s['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s['sequence'], $resp);
        $this->assertEquals(1, $s['number'], $resp);
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $snap = readTicketSnapshot($this->cash, $s['sequence'], $s['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap, $tkt);
        $this->assertEquals(0, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    public function testSingleOldDesktop() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = [
            'dispOrder' => 0, 'amount' => 11,
            'currencyAmount' => 11,
            'type' => $this->pm->getReference(),
            'desktop' => true,
            'currency' => $this->curr->getId()
        ];
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(1, count($data['successes']), $resp);
        $s = $data['successes'][0];
        $this->assertEquals($this->cash->getId(), $s['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s['sequence'],
                $resp);
        $this->assertEquals(1, $s['number'], $resp);
        $snap = readTicketSnapshot($this->cash, $s['sequence'], $s['number'],
                $this->dao);
        $tkt['payments'][0]['paymentMode'] = $this->pm->getId(); // for equality
        $this->assertTicketModelEqStruct($snap, $tkt);
        $this->assertEquals(0, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    /** @depends testSingle
     * Send malformed data. */
    public function TestSyntaxError() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        $data = substr(json_encode([$tkt]), 1);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . $data);
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

    /** @depends testSingle
     * Send ticket with invalid data. */
    public function testInvalidData() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        $tkt['payments'][0]['paymentMode'] = null;
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(0, count($data['successes']), $resp);
        $this->assertEquals(1, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
        $s = $data['failures'][0];
        $err = json_decode($s['message'], true);
        $this->assertNotNull($err);
        $err = $err['error'];
        $this->assertEquals('InvalidField', $err['error']);
        $this->assertEquals(InvalidFieldException::CSTR_NOT_NULL,
                $err['constraint']);
        $this->assertEquals('paymentMode', $err['field']);
        // Check that the fiscal ticket is registered
        $ftkts = $this->dao->search(FiscalTicket::class,
                new DAOCondition('sequence', '=', FiscalTicket::getGeneralFailureSequence()),
                null, null, '-number');
        $this->assertEquals(2, count($ftkts));
        $ftkt = $ftkts[0];
        $fail = json_decode($ftkt->getContent(), true);
        $this->assertNotNull($fail);
        $inputFisc = $fail['input'];
        $this->assertEquals($tkt['sequence'], $inputFisc['sequence']);
        $this->assertEquals($tkt['number'], $inputFisc['number']);
        $this->assertEquals(null, $inputFisc['payments'][0]['paymentMode']);
        $errFisc = $fail['failure'];
        $this->assertEquals('InvalidField', $errFisc['error']);
        $this->assertEquals(InvalidFieldException::CSTR_NOT_NULL,
                $errFisc['constraint']);
        $this->assertEquals('paymentMode', $errFisc['field']);
   }

    /** @depends testSingle
     * Write multiple regular tickets. */
    public function testMultiple() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        $tkt2 = $this->getBaseTicket(2);
        $tkt2['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt2['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt2['payments'][] = $this->getBasePayment(0, 11.0);
        $tkt3 = $this->getBaseTicket(3);
        $tkt3['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt3['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt3['payments'][] = $this->getBasePayment(0, 11.0);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt, $tkt2, $tkt3]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(3, count($data['successes']), $resp);
        $s1 = $data['successes'][0];
        $s2 = $data['successes'][1];
        $s3 = $data['successes'][2];
        $this->assertEquals($this->cash->getId(), $s1['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s1['sequence'], $resp);
        $this->assertEquals(1, $s1['number'], $resp);
        $snap1 = readTicketSnapshot($this->cash, $s1['sequence'], $s1['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap1, $tkt);
        $this->assertEquals($this->cash->getId(), $s2['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s2['sequence'], $resp);
        $this->assertEquals(2, $s2['number'], $resp);
        $snap2 = readTicketSnapshot($this->cash, $s2['sequence'], $s2['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap2, $tkt2);
        $this->assertEquals($this->cash->getId(), $s3['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s3['sequence'], $resp);
        $this->assertEquals(3, $s3['number'], $resp);
        $snap3 = readTicketSnapshot($this->cash, $s3['sequence'], $s3['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap3, $tkt3);
        $this->assertEquals(0, count($data['failures']), $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    /** @depends testMultiple
     * Write multiple tickets with the last one being rejected. */
    public function testRejectLast() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        $tkt2 = $this->getBaseTicket(1);
        $tkt2['lines'][] = $this->getBaseLine(0, 'test2', 11.0);
        $tkt2['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt2['payments'][] = $this->getBasePayment(0, 11.0);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt, $tkt2]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(1, count($data['successes']), $resp);
        $s = $data['successes'][0];
        $this->assertEquals($this->cash->getId(), $s['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s['sequence'], $resp);
        $this->assertEquals(1, $s['number'], $resp);
        $snap1 = readTicketSnapshot($this->cash, $s['sequence'], $s['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap1, $tkt);
        $this->assertEquals(1, count($data['failures']), $resp);
        $r = $data['failures'][0];
        $this->assertEquals($this->cash->getId(), $r['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $r['sequence'], $resp);
        $this->assertEquals(1, $r['number'], $resp);
        $e = json_decode($r['message'], true);
        $this->assertNotNull($e);
        $this->assertEquals('InvalidRecord', $e['error']);
        $this->assertEquals(InvalidRecordException::CSTR_READ_ONLY,
                $e['constraint']);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

    /** @depends testMultiple
     * Write multiple valid tickets with one invalid inside the run. */
    public function testRejectMiddle() {
        $tkt = $this->getBaseTicket(1);
        $tkt['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt['payments'][] = $this->getBasePayment(0, 11.0);
        $tkt2 = $this->getBaseTicket(1);
        $tkt2['lines'][] = $this->getBaseLine(0, 'test2', 11.0);
        $tkt2['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt2['payments'][] = $this->getBasePayment(0, 11.0);
        $tkt3 = $this->getBaseTicket(3);
        $tkt3['lines'][] = $this->getBaseLine(0, 'test', 11.0);
        $tkt3['taxes'][] = $this->getBaseTax(10.0, 1.0);
        $tkt3['payments'][] = $this->getBasePayment(0, 11.0);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                'tickets=' . json_encode([$tkt, $tkt2, $tkt3]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data, $resp);
        $this->assertEquals(2, count($data['successes']), $resp);
        $s1 = $data['successes'][0];
        $s3 = $data['successes'][1];
        $this->assertEquals($this->cash->getId(), $s1['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s1['sequence'], $resp);
        $this->assertEquals(1, $s1['number'], $resp);
        $snap1 = readTicketSnapshot($this->cash, $s1['sequence'], $s1['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap1, $tkt);
        $this->assertEquals($this->cash->getId(), $s3['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $s3['sequence'], $resp);
        $this->assertEquals(3, $s3['number'], $resp);
        $snap3 = readTicketSnapshot($this->cash, $s3['sequence'], $s3['number'], $this->dao);
        $this->assertTicketModelEqStruct($snap3, $tkt3);
        $this->assertEquals(1, count($data['failures']), $resp);
        $r2 = $data['failures'][0];
        $this->assertEquals($this->cash->getId(), $r2['cashRegister'], $resp);
        $this->assertEquals($this->session->getSequence(), $r2['sequence'], $resp);
        $this->assertEquals(1, $r2['number'], $resp);
        $this->assertEquals(0, count($data['errors']), $resp);
    }

}
