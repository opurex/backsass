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

require_once(dirname(dirname(dirname(__FILE__))) . '/common_load.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/common_fiscalticket.php');

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

    public function testImportNew() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $eos = createEos($tkt2);
        $tkts = [$tkt->toStruct(), $tkt2->toStruct(), $eos->toStruct()];
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token, 'Content-type: application/json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
        $this->assertEquals(3, count($result['successes']));
        $this->assertEquals(0, count($result['failures']));
        assertFiscalTicketModelEqStruct($tkt, $result['successes'][0], $this);
        assertFiscalTicketModelEqStruct($tkt2, $result['successes'][1], $this);
        assertFiscalTicketModelEqStruct($eos, $result['successes'][2], $this);
        $snap1 = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 1, $this->dao);
        $snap2 = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 2, $this->dao);
        $snapEos = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 0, $this->dao);
        assertFiscalTicketModelEqStruct($snap1, $tkts[0], $this);
        assertFiscalTicketModelEqStruct($snap2, $tkts[1], $this);
        assertFiscalTicketModelEqStruct($snapEos, $tkts[2], $this);
        $this->assertEquals(3, $this->dao->count(FiscalTicket::class));
    }

    /** @depends testImportNew
     * Reimport existing ticket and add a few. */
    public function testReimport() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $eos = createEos($tkt);
        $tkts = [$tkt->toStruct(), $eos->toStruct()];
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token, 'Content-type: application/json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
        $this->assertEquals(2, count($result['successes']), 'Initial import failed.');
        // Create a new ticket
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $eos->setDate(new \DateTime('2018-01-01 15:03'));
        $eos->sign($tkt2);
        $tkts = [$tkt->toStruct(), $tkt2->toStruct(), $eos->toStruct()];
        // Import the whole
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token, 'Content-type: application/json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
        $this->assertEquals(3, count($result['successes']));
        $this->assertEquals(0, count($result['failures']));
        assertFiscalTicketModelEqStruct($tkt, $result['successes'][0], $this);
        assertFiscalTicketModelEqStruct($tkt2, $result['successes'][1], $this);
        assertFiscalTicketModelEqStruct($eos, $result['successes'][2], $this);
        $snap1 = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 1, $this->dao);
        $snap2 = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 2, $this->dao);
        $snapEos = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 0, $this->dao);
        assertFiscalTicketModelEqStruct($snap1, $tkts[0], $this);
        assertFiscalTicketModelEqStruct($snap2, $tkts[1], $this);
        assertFiscalTicketModelEqStruct($snapEos, $tkts[2], $this);
        $this->assertEquals(3, $this->dao->count(FiscalTicket::class));
    }

    /** @depends testImportNew */
    public function testImportRewrite() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $eos = createEos($tkt);
        $tkts = [$tkt->toStruct(), $eos->toStruct()];
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token, 'Content-type: application/json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
        $this->assertEquals(2, count($result['successes']), 'Initial import failed.');
        // Update that ticket and reimport it
        $tkts[0]['content'] = 'Metamorphosis';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token, 'Content-type: application/json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
        $this->assertEquals(1, count($result['successes']));
        $this->assertEquals(1, count($result['failures']));
        $fail = $result['failures'][0]['ticket'];
        $this->assertEquals($tkts[0]['type'], $fail['type']);
        $this->assertEquals($tkts[0]['sequence'], $fail['sequence']);
        $this->assertEquals($tkts[0]['number'], $fail['number']);
        $this->assertEquals($tkts[0]['content'], $fail['content']);
        $this->assertEquals($tkts[0]['signature'], $fail['signature']);
        $this->assertEquals('Trying to override an existing fiscal ticket.',
                $result['failures'][0]['reason']);
        $this->assertEquals(2, $this->dao->count(FiscalTicket::class));
        $snap = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 1, $this->dao);
        $this->assertEquals($tkt->getContent(), $snap->getContent());
    }

    /** @depends testImportNew */
    public function testImportReject() {
        // Create a ticket with an invalid date
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $date = new \DateTime();
        $date->setTimestamp(253402297200 + 100000); // rejected date
        $tkt->setDate($date);
        $tkt->setContent('Back from the future');
        $tkt->sign(null);
         // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token, 'Content-type: application/json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode([$tkt]));
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
    }

    public function testImportUnsupportedEncoding() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $eos = createEos($tkt2);
        $tkts = [$tkt->toStruct(), $tkt2->toStruct(), $eos->toStruct()];
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                'Content-type: application/json',
                'Content-Encoding: json']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(415, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals('Unsupported Content-Encoding "json", must be "zip" or not set.', $resp);
    }

    /** @depends testImportNew */
    public function testImportZip() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $eos = createEos($tkt2);
        $tkts = [$tkt->toStruct(), $tkt2->toStruct(), $eos->toStruct()];
        // Create Zip file
        $zipFileName = tempnam(sys_get_temp_dir(), 'pttest_import_zip');
        $zip = new \ZipArchive();
        $zip->open($zipFileName, \ZipArchive::CREATE);
        $zip->addFromString('tkts', json_encode($tkts));
        $zip->close();
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                'Content-type: application/json',
                'Content-Encoding: zip']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, file_get_contents($zipFileName));
        $resp = curl_exec($this->curl);
        unlink($zipFileName);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $result = json_decode($resp, true);
        $this->assertEquals(3, count($result['successes']));
        $this->assertEquals(0, count($result['failures']));
        assertFiscalTicketModelEqStruct($tkt, $result['successes'][0], $this);
        assertFiscalTicketModelEqStruct($tkt2, $result['successes'][1], $this);
        assertFiscalTicketModelEqStruct($eos, $result['successes'][2], $this);
        $snap1 = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 1, $this->dao);
        $snap2 = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 2, $this->dao);
        $snapEos = readFiscalTicketSnapshot(FiscalTicket::TYPE_TICKET, '1', 0, $this->dao);
        assertFiscalTicketModelEqStruct($snap1, $tkts[0], $this);
        assertFiscalTicketModelEqStruct($snap2, $tkts[1], $this);
        assertFiscalTicketModelEqStruct($snapEos, $tkts[2], $this);
        $this->assertEquals(3, $this->dao->count(FiscalTicket::class));
    }

    public function testImportZipMultifile() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $eos = createEos($tkt2);
        $tkts = [$tkt->toStruct(), $tkt2->toStruct(), $eos->toStruct()];
        // Create Zip file
        $zipFileName = tempnam(sys_get_temp_dir(), 'pttest_import_zip');
        $zip = new \ZipArchive();
        $zip->open($zipFileName, \ZipArchive::CREATE);
        $zip->addFromString('tkts', json_encode($tkts));
        $zip->addFromString('tkts2', json_encode($tkts));
        $zip->close();
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                'Content-type: application/json',
                'Content-Encoding: zip']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, file_get_contents($zipFileName));
        $resp = curl_exec($this->curl);
        unlink($zipFileName);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }

    public function testImportZipNotZip() {
        // Create a few tickets
        $tkt = new FiscalTicket();
        $tkt->setType(FiscalTicket::TYPE_TICKET);
        $tkt->setSequence('1');
        $tkt->setNumber(1);
        $tkt->setDate(new \DateTime('2018-01-01 12:00'));
        $tkt->setContent('Nippon Ichi!');
        $tkt->sign(null);
        $tkt2 = new FiscalTicket();
        $tkt2->setType(FiscalTicket::TYPE_TICKET);
        $tkt2->setSequence('1');
        $tkt2->setNumber(2);
        $tkt2->setDate(new \DateTime('2018-01-01 15:02'));
        $tkt2->setContent('Mewtwo');
        $tkt2->sign($tkt);
        $eos = createEos($tkt2);
        $tkts = [$tkt->toStruct(), $tkt2->toStruct(), $eos->toStruct()];
        // Import
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/fiscal/import'));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                'Content-type: application/json',
                'Content-Encoding: zip']);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($tkts));
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }
}
