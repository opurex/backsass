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

use \Firebase\JWT\JWT;
use \Pasteque\Server\System\Login;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class LoginTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testIssueToken() {
        $payload = ['iat' => 100, 'user' => 'test'];
        $secret = 'This is a secret, or not.';
        $jwt = JWT::encode($payload, $secret);
        $this->assertEquals($jwt, Login::issueToken('test', $secret, 100));
    }

    public function testIsValidOk() {
        $payload = new \stdClass();
        $payload->iat = 100; $payload->user = 'test';
        $this->assertTrue(Login::isValid($payload, 50, 110));
    }

    public function testIsValidNoIAT() {
        $payload = new \stdClass();
        $payload->user = 'test';
        $this->assertFalse(Login::isValid($payload, 50, 110));
    }

    public function testIsValidTimeout() {
        $payload = new \stdClass();
        $payload->iat = 100; $payload->user = 'test';
        $this->assertFalse(Login::isValid($payload, 10, 150));
    }

    public function testGetLoggedUserIdNull() {
        $this->expectException(\UnexpectedValueException::class);
        Login::getLoggedUserId(null, 'secret', 10);
    }

    public function testGetLoggedUserIdCorrupted() {
        $this->expectException(\UnexpectedValueException::class);
        $this->assertNull(Login::getLoggedUserId('not a jwt', 'secret', 10));
    }

    /** @depends testIssueToken */
    public function testGetLoggedUserIdWrongToken() {
        $payload = ['iat' => 100, 'user' => 'test'];
        $secret = 'This is a secret, or not.';
        $jwt = JWT::encode($payload, $secret);
        $this->assertNull(Login::getLoggedUserId($jwt, 'The true secret', 50, 120));
    }

    /** @depends testIssueToken
     * @depends testIsValidTimeout */
    public function testGetLoggedUserIdTimeout() {
        $payload = ['iat' => 100, 'user' => 'test'];
        $secret = 'This is a secret, or not.';
        $jwt = JWT::encode($payload, $secret);
        $this->assertNull(Login::getLoggedUserId($jwt, $secret, 10, 120));
    }

    /** @depends testIssueToken
     * @depends testIsValidOk */
    public function testGetLoggedUserIdValid() {
        $payload = ['iat' => 100, 'user' => 'test'];
        $secret = 'This is a secret, or not.';
        $jwt = JWT::encode($payload, $secret);
        $this->assertEquals('test', Login::getLoggedUserId($jwt, $secret, 50, 120));
    }
}
