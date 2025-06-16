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

use \Pasteque\Server\System\DateUtils;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class DateUtilsTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testToTimestampNull() {
        $this->assertNull(DateUtils::toTimestamp(null));
    }

    public function testToTimestampTimestamp() {
        $timestamp =  1171502725;
        $this->assertEquals($timestamp, DateUtils::toTimestamp($timestamp));
    }

    public function testToTimestampDateTime() {
        $timestamp =  1171502725;
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $this->assertEquals($timestamp, DateUtils::toTimestamp($date));
    }

    public function testToTimestampInvalid() {
        $this->assertNull(DateUtils::toTimestamp("blabla"));
    }

    public function testReadTimestamp() {
        $timestamp = 1171502725;
        $date = DateUtils::readTimestamp($timestamp);
        $this->assertNotEquals(false, $date);
        $this->assertEquals($timestamp, $date->getTimestamp());
    }

    public function testReadTimestampNok() {
        $date = 'date';
        $this->assertFalse(DateUtils::readTimestamp($date));
    }

    /** @depends testReadTimestamp */
    public function testReadDateTimestamp() {
        $timestamp = 1171502725;
        $date = DateUtils::readDate($timestamp);
        $this->assertNotEquals(false, $date);
        $this->assertEquals($timestamp, $date->getTimestamp());
    }

    /**
     * Test rejecting a 5-digits year date because Doctrine
     * cannot handle them back from the database and crashes on read.
     */
    public function testReadDateTimestamp40k() {
        $timestamp = 253402297200;
        $date = DateUtils::readDate($timestamp);
        $this->assertEquals(false, $date);
    }

    public function testReadDateTimestampString() {
        $timestamp = '1171502725';
        $date = DateUtils::readDate($timestamp);
        $this->assertNotEquals(false, $date);
        $this->assertEquals($timestamp, $date->getTimestamp()); 
    }

    public function testReadDateDateTime() {
        $timestamp =  1171502725;
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $d2 = DateUtils::readDate($date);
        $this->assertNotEquals(false, $d2);
        $this->assertEquals($timestamp, $d2->getTimestamp());
    }

    public function testReadDateNull() {
        $input = null;
        $this->assertNull(DateUtils::readDate($input));
    }

    public function testReadDateNok() {
        $input = 'not a date';
        $this->assertFalse(DateUtils::readDate($input));
    }

    public function testReadDateString() {
        $date = DateUtils::readDate('2017-10-01');
        $this->assertNotEquals(false, $date);
        $this->assertEquals('2017-10-01 00:00:00',
                $date->format('Y-m-d H:i:s'));
        $date = DateUtils::readDate('2017-10-01 17:30');
        $this->assertNotEquals(false, $date);
        $this->assertEquals('2017-10-01 17:30:00',
                $date->format('Y-m-d H:i:s'));
    }

    /**
     * Test rejecting a 5-digits year date because Doctrine
     * cannot handle them back from the database and crashes on read.
     */
    public function testReadDateString40k() {
        $date = DateUtils::readDate('10000-01-01 00:00:00');
        $this->assertEquals(false, $date);
    }

    /** @depends testReadDateTimestamp
     * @depends testReadDateDateTime */
    public function testEquals() {
        $t1 = 1171502725;
        $t2 = 1171502727;
        $dt1 = $date = new \DateTime();
        $date->setTimestamp($t1);
        $dt2 = $date = new \DateTime();
        $dt2->setTimestamp($t2);
        $this->assertFalse(DateUtils::equals("a", $t1));
        $this->assertFalse(DateUtils::equals("a", null));
        $this->assertFalse(DateUtils::equals(null, "a"));
        $this->assertTrue(DateUtils::equals(null, null));
        $this->assertTrue(DateUtils::equals($t1, $t1));
        $this->assertTrue(DateUtils::equals($t1, $dt1));
        $this->assertTrue(DateUtils::equals($dt1, $t1));
        $this->assertTrue(DateUtils::equals($dt1, $dt1));
        $this->assertFalse(DateUtils::equals($t1, $t2));
        $this->assertFalse(DateUtils::equals($t1, $dt2));
        $this->assertFalse(DateUtils::equals($dt2, $t1));
        $this->assertFalse(DateUtils::equals($dt1, $dt2));
    }
}
