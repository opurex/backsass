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
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpVersionTest extends TestCase
{
    private $curl;
    private static $token;
    private static $dao;
    private static $version;
    public static function setUpBeforeClass(): void {
        static::$token = obtainToken();
        static::$version = new Option();
        static::$version->setName('dblevel');
        static::$version->setContent(VersionAPI::LEVEL);
        static::$version->setSystem(true);
        global $dbInfo;
        static::$dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        static::$dao->write(static::$version);
        static::$dao->commit();
    }

    public static function tearDownAfterClass(): void {
        static::$dao->delete(static::$version);
        static::$dao->commit();
        static::$dao->close();
    }    

    protected function setUp(): void {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/version'));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token]);
    }

    protected function tearDown(): void {
        curl_close($this->curl);
    }

    public function testVersion() {
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertNotNull($data);
        $this->assertEquals(VersionAPI::VERSION, $data['version']);
        $this->assertEquals(VersionAPI::REVISION, $data['revision']);
        $this->assertEquals(VersionAPI::LEVEL, $data['level']);
    }
}
