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

use \Pasteque\Server\CommonAPI\VersionAPI;
use \Pasteque\Server\Model\Option;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class VersionAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new VersionAPI($this->dao);
    }

    protected function tearDown(): void {
        $all = $this->dao->search(Option::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetNoValue() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('dblevel option does not exist. The database is probably not initialized.');
        $version = $this->api->get();
    }

    public function testGetInvalidValue() {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('dblevel abc option is invalid.');
        $opt = new Option();
        $opt->setName('dblevel');
        $opt->setSystem(true);
        $opt->setContent('abc');
        $this->dao->write($opt);
        $this->dao->commit();
        $this->api->get();
    }

    public function testGet() {
        $opt = new Option();
        $opt->setName('dblevel');
        $opt->setSystem(true);
        $opt->setContent('1');
        $this->dao->write($opt);
        $this->dao->commit();
        $version = $this->api->get();
        $this->assertEquals(VersionAPI::VERSION, $version->get('version'));
        $this->assertEquals(1, $version->get('level'));
    }

    /** @depends testGet */
    public function testSetInt() {
        $this->api->setLevel(3);
        $version = $this->api->get();
        $this->assertNotNull($version);
        $this->assertEquals(VersionAPI::VERSION, $version->get('version'));
        $this->assertEquals(3, $version->get('level'));
    }

    /** @depends testGet */
    public function testSetString() {
        $this->api->setLevel('4');
        $version = $this->api->get();
        $this->assertNotNull($version);
        $this->assertEquals(VersionAPI::VERSION, $version->get('version'));
        $this->assertEquals(4, $version->get('level'));
    }

    public function testSetNull() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid level value.');
        $this->api->setLevel(null);
    }

    public function testSetZero() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid level value.');
        $this->api->setLevel(0);
    }

    /** @depends testSetInt */
    public function testSetUpdate() {
        $this->api->setLevel(3);
        $this->api->setLevel(6);
        $version = $this->api->get();
        $this->assertNotNull($version);
        $this->assertEquals(VersionAPI::VERSION, $version->get('version'));
        $this->assertEquals(6, $version->get('level'));
    }
}
