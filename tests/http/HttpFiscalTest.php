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

use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpFiscalTest extends TestCase
{
    private $curl;
    private $dao;
    private static $token;

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
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        $all = $this->dao->search(FiscalTicket::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testExportEmpty() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/export'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals('[]', $resp);
    }

    public function testExport() {
        // Write a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber('1');
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber('2');
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $this->dao->write($tkt);
        $this->dao->write($tkt2);
        $this->dao->commit();
        // Export
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/export'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $export = json_decode($resp, true);
        $this->assertNotEquals(null, $export, 'Unable to parse response.');
        $this->assertEquals(2, count($export), 'Unexpected number of tickets.');
        $this->assertEquals('1', $export[0]['number'], 'Wrong export order.');
        $this->assertEquals('2', $export[1]['number'], 'Invalid exported data.');
    }

    public function testExportDate() {
        // Write a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber('1');
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber('2');
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $tkt3 = new FiscalTicket();
        $tkt3->setType(FiscalTicket::TYPE_TICKET);
        $tkt3->setSequence('1');
        $tkt3->setNumber('3');
        $tkt3->setDate(new \DateTime('2018-01-01 18:05'));
        $tkt3->setContent('trois');
        $tkt3->sign($tkt2);
        $this->dao->write($tkt);
        $this->dao->write($tkt2);
        $this->dao->write($tkt3);
        $this->dao->commit();
        // Export
        $queryParams = http_build_query([
                'dateStart' => '2018-01-01 15:00',
                'dateStop' => '2018-01-01 16:00',
        ]);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl(sprintf('api/fiscal/export?%s', $queryParams)));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals(1, count($jsResp));
        $this->assertEquals('2', $jsResp[0]['number']);
    }

    public function testExportDateInvalid() {
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/fiscal/export?dateStart=notadate'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('InvalidDate', $jsResp['constraint']);
        $this->assertEquals('dateStart', $jsResp['field']);
        $this->assertEquals('notadate', $jsResp['value']);
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/fiscal/export?dateStop=notadate'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('InvalidDate', $jsResp['constraint']);
        $this->assertEquals('dateStop', $jsResp['field']);
        $this->assertEquals('notadate', $jsResp['value']);
    }

    /** Test that import is not available on a regular server. */
    public function testImport() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, "{}");
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }
}

