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

use \Pasteque\Server\System\Login;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpLoginTest extends TestCase
{
    private $curl;
    protected function setUp(): void {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    protected function tearDown(): void {
        curl_close($this->curl);
    }

    /** Send a call without login. Expect it to be rejected. */
    public function testReject() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/version'));
        curl_exec($this->curl);
        $this->assertEquals(403, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }

    public function testRejectGet() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/login'));
        curl_exec($this->curl);
        $this->assertEquals(405, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
    }

    public function testNoCredentials() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/login'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals('null', $resp);
    }

    public function testWrongCredentials() {
        global $cfg;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/login'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['user' => $cfg['http/user'], 'password' => 'notme']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals('null', $resp);
    }

    public function testGoodCredentials() {
        global $cfg;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/login'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['user' => $cfg['http/user'],
                        'password' => $cfg['http/password']]);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertNotEquals('null', $resp);
        // TODO: check the Token header in response
    }

}
