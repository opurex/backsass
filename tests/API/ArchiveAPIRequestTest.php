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

use \Pasteque\Server\CommonAPI\ArchiveAPI;
use \Pasteque\Server\Exception\ConfigurationException;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Archive;
use \Pasteque\Server\Model\ArchiveRequest;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");
require_once(dirname(dirname(__FILE__)) . "/PastequeTestCase.php");

class ArchiveAPIRequestTest extends PastequeTestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        global $cfg;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $gpgPath = null;
        $basePath = dirname(dirname(dirname(__FILE__)));
        if (substr($cfg['gpg/path'], 0, 1) == '.') {
            $gpgPath = $basePath . '/'. $cfg['gpg/path'];
        } else {
            $gpgPath = $cfg['gpg/path'];
        }
        $this->api = new ArchiveAPI($this->dao, $cfg['http/user'], $gpgPath,
                $cfg['gpg/fingerprint']);
    }

    protected function tearDown(): void {
        $all = $this->dao->search(ArchiveRequest::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testRequestArchive() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $request2 = $this->api->addRequest($start, $stop);
        $this->assertEquals($request->getId() + 1, $request2->getId());
        $snapReq = $this->dao->readSnapshot(ArchiveRequest::class,
                $request->getId());
        $snapReq2 = $this->dao->readSnapshot(ArchiveRequest::class,
                $request2->getId());
        $this->assertNotNull($snapReq);
        $this->assertNotNull($snapReq2);
        $this->assertTrue(DateUtils::equals($start,
                $snapReq->getStartDate()));
        $this->assertTrue(DateUtils::equals($stop,
                $snapReq->getStopDate()));
        $this->assertTrue(DateUtils::equals($start,
                $snapReq2->getStartDate()));
        $this->assertTrue(DateUtils::equals($stop,
                $snapReq2->getStopDate()));
    }

    public function testRequestArchiveWrongDates() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest($start, 'nope');
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertInvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'stopDate',
                    ['startDate' => $start, 'stopDate' => 'nope'],
                    'nope', $e);
        }
        $this->assertTrue($exceptionThrown);
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest('nope', $stop);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertInvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'startDate',
                    ['startDate' => 'nope', 'stopDate' => $stop],
                    'nope', $e);
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testRequestArchiveInverted() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest($stop, $start);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $interval = $start->diff($stop);
            $this->assertInvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATERANGE,
                    ArchiveRequest::class, 'startDate-stopDate',
                    ['startDate' => $stop, 'stopDate' => $start],
                    $interval->format('-%y-%m-%d %H:%i:%s'), $e);
        }
    }

    public function testRequestFutureDate() {
        $now = new \DateTime();
        $start = $now->add(new \DateInterval('P1D'));
        $now = new \DateTime();
        $stop = $now->add(new \DateInterval('P3D'));
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest($start, $stop);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertInvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'startDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $start, $e);
        }
        $this->assertTrue($exceptionThrown);
        $now = new \DateTime();
        $start = $now->sub(new \DateInterval('P14D'));
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest($start, $stop);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertInvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATE,
                    ArchiveRequest::class, 'stopDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $stop, $e);
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testRequestArchiveOneYearAndSo() {
        $start = new \DateTime('2017-01-01 3:00:00');
        $stop = new \DateTime('2018-01-01 6:00:00');
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest($start, $stop);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
        }
        $this->assertFalse($exceptionThrown); 
    }

    public function testRequestArchiveMoreThanOneYear() {
        $start = new \DateTime('2017-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $exceptionThrown = false;
        try {
            $request = $this->api->addRequest($start, $stop);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $interval = $start->diff($stop);
            $this->assertInvalidFieldException(
                    InvalidFieldException::CSTR_INVALID_DATERANGE,
                    ArchiveRequest::class, 'startDate-stopDate',
                    ['startDate' => $start, 'stopDate' => $stop],
                    $interval->format('%y-%m-%d %H:%i:%s'), $e);
        }
        $this->assertTrue($exceptionThrown); 
    }

    public function testListRequestsEmpty() {
        $list = $this->api->getRequests();
        $this->assertEquals(0, count($list));
    }

    /** @depends testRequestArchive */
    public function testGetRequests() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $list = $this->api->getRequests();
        $this->assertEquals(1, count($list)); 
        $this->assertEquals($request->getId(), $list[0]->getId('id'));
    }

    public function testGetFirstRequestEmpty() {
        $request = $this->api->getFirstAvailableRequest();
        $this->assertNull($request);
    }

    /** @depends testRequestArchive */
    public function testGetFirstRequest() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $request2 = $this->api->addRequest($start, $stop);
        $req = $this->api->getFirstAvailableRequest();
        $this->assertEquals($request->getId(), $req->getId());
    }

    /** @depends testRequestArchive */
    public function testGetFirstRequestProcessing() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $request2 = $this->api->addRequest($start, $stop);
        $request->setProcessing(true);
        $request2->setProcessing(true);
        $this->dao->write($request);
        $this->dao->write($request2);
        $this->dao->commit();
        $req = $this->api->getFirstAvailableRequest();
        $this->assertNull($req);
    }

    /** @depends testGetFirstRequest */
    public function testGetFirstRequestMixed() {
        $start = new \DateTime('2018-01-01 13:00:00');
        $stop = new \DateTime('2018-03-01 15:00:00');
        $request = $this->api->addRequest($start, $stop);
        $request2 = $this->api->addRequest($start, $stop);
        $request->setProcessing(true);
        $this->dao->write($request);
        $this->dao->commit();
        $req = $this->api->getFirstAvailableRequest();
        $this->assertNull($req);
    }
}
