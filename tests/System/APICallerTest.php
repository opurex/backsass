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

use \Pasteque\Server\Exception\APINotFoundException;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class APICallerTest extends TestCase {

    private static $app;

    public static function setUpBeforeClass(): void {
        static::$app = new AppContext();
    }

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testFormatAPIName() {
        $this->assertEquals('LoginAPI', APICaller::formatAPIName('login'), 'ucfirst failed');
        $this->assertEquals('/loginAPI', APICaller::formatAPIName('../login'), '.. escape failed');
        $this->assertEquals('LoginAPI', APICaller::formatAPIName('loginAPI'), 'API suffix failed');
        $this->assertEquals('LoginAPI', APICaller::formatAPIName('loginapi'), 'api suffix failed');
        $this->assertEquals('LoginAPI', APICaller::formatAPIName('LOGIN'), 'strtolower failed');
        $this->assertEquals('LoginAPI', APICaller::formatAPIName('LogIn'), 'Multicap failed');
    }

    /** @depends testFormatAPIName */
    public function testCheckPermission() {
        $this->assertTrue(APICaller::checkPermission(null, 'login', 'login'));
        $this->assertTrue(APICaller::checkPermission(null, 'login', 'getToken'));
        $this->assertFalse(APICaller::checkPermission(null, 'login', 'other'));
        $this->assertFalse(APICaller::checkPermission(null, 'other', 'login'));
        // This will fail once user permissions are set at API level
        $this->assertTrue(APICaller::checkPermission('user', 'anything', 'anything'));
    }

    public function testIsAllowedOrigin() {
        $this->assertTrue(APICaller::isAllowedOrigin('origin', '*'));
        $this->assertTrue(APICaller::isAllowedOrigin('origin', 'origin'));
        $this->assertTrue(APICaller::isAllowedOrigin('origin', array('somewhere', 'origin')));
        $this->assertFalse(APICaller::isAllowedOrigin('origin', 'no'));
        $this->assertFalse(APICaller::isAllowedOrigin('origin', array('no', 'is_no')));
    }

    public function testGetCORSHeaders() {
        $headers = APICaller::getCORSHeaders('origin', 'origin', 100);
        $this->assertEquals(6, count(array_keys($headers)));
        $this->assertEquals($headers['Access-Control-Allow-Methods'],
                'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $this->assertEquals('origin', $headers['Access-Control-Allow-Origin']);
        $this->assertFalse($headers['Access-Control-Allow-Credentials']);
        $this->assertEquals(100, $headers['Access-Control-Max-Age']);
        $this->assertEquals(Login::TOKEN_HEADER,
                $headers['Access-Control-Expose-Headers']);
        $this->assertEquals(Login::TOKEN_HEADER . ', Content-Type',
                $headers['Access-Control-Allow-Headers']);
        $headers = APICaller::getCORSHeaders('origin', array('allowed', 'origin'), 200);
        $this->assertEquals(7, count(array_keys($headers)));
        $this->assertEquals($headers['Access-Control-Allow-Methods'],
                'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $this->assertEquals('origin', $headers['Access-Control-Allow-Origin']);
        $this->assertFalse($headers['Access-Control-Allow-Credentials']);
        $this->assertEquals(200, $headers['Access-Control-Max-Age']);
        $this->assertEquals(Login::TOKEN_HEADER,
                $headers['Access-Control-Expose-Headers']);
        $this->assertEquals(Login::TOKEN_HEADER . ', Content-Type',
                $headers['Access-Control-Allow-Headers']);
        $this->assertEquals('Origin', $headers['Vary']);
    }

    public function testRunClassNotFound() {
        $result = APICaller::run(static::$app, 'someAPI', 'method', array());
        $this->assertEquals(APIResult::STATUS_CALL_REJECTED,  $result->getStatus());
        $error = $result->getContent();
        $this->assertNotNull($error);
        $this->assertEquals(APINotFoundException::class, get_class($error));
        $this->assertEquals('SomeAPI', $error->getAPI());
        $this->assertNull($error->getAction());
        $this->assertEquals("API SomeAPI doesn't exist.",
                $error->getMessage());
    }

    /** @depends testFormatAPIName
     * @depends testRunClassNotFound */
    public function testRunMethodNotFound() {
        $result = APICaller::run(static::$app, 'test', 'method', array());
        $this->assertEquals(APIResult::STATUS_CALL_REJECTED,  $result->getStatus());
        $error = $result->getContent();
        $this->assertNotNull($error);
        $this->assertEquals(APINotFoundException::class, get_class($error));
        $this->assertEquals('TestAPI', $error->getAPI());
        $this->assertEquals('method', $error->getAction());
        $this->assertNull($error->getMinArgc());
        $this->assertEquals("API TestAPI->method doesn't exist.",
                $error->getMessage()); 
    }

    /** @depends testRunMethodNotFound */
    public function testRunWrongArgs() {
        // Give too much arguments
        $result = APICaller::run(static::$app, 'test', 'noParam', [1]);
        $this->assertEquals(APIResult::STATUS_CALL_REJECTED,  $result->getStatus());
        $error = $result->getContent();
        $this->assertNotNull($error);
        $this->assertEquals(APINotFoundException::class, get_class($error));
        $this->assertEquals('TestAPI', $error->getAPI());
        $this->assertEquals('noParam', $error->getAction());
        $this->assertEquals(0, $error->getMinArgc());
        $this->assertEquals(0, $error->getMaxArgc());
        $this->assertEquals(1, $error->getGivenArgc());
        $this->assertEquals("API TestAPI->noParam expects 0 arguments (1 given).",
                $error->getMessage());
        // Give not enough arguments
        $result = APICaller::run(static::$app, 'test', 'params', [1]);
        $this->assertEquals(APIResult::STATUS_CALL_REJECTED,  $result->getStatus());
        $error = $result->getContent();
        $this->assertNotNull($error);
        $this->assertEquals(APINotFoundException::class, get_class($error));
        $this->assertEquals('TestAPI', $error->getAPI());
        $this->assertEquals('params', $error->getAction());
        $this->assertEquals(2, $error->getMinArgc());
        $this->assertEquals(2, $error->getMaxArgc());
        $this->assertEquals(1, $error->getGivenArgc());
        $this->assertEquals("API TestAPI->params expects 2 arguments (1 given).",
                $error->getMessage()); 
    }

    /** @depends testFormatAPIName */
    public function testRunNoArgsOK() {
        $result = APICaller::run(static::$app, 'test', 'noParam', array());
        $this->assertEquals(APIResult::STATUS_CALL_OK, $result->getStatus(),
            sprintf('Call rejected: %', json_encode($result->getContent())));
        $this->assertEquals(\Pasteque\Server\API\TestAPI::NO_PARAM_RESULT, $result->getContent());
    }

    /** @depends testFormatAPIName */
    public function testRunArgsOK() {
        $args = array(1, 2);
        $result = APICaller::run(static::$app, 'test', 'params', $args);
        $this->assertEquals(APIResult::STATUS_CALL_OK, $result->getStatus(),
            sprintf('Call rejected: %s', json_encode($result->getContent())));
        $this->assertEquals($args[0] + $args[1], $result->getContent());
    }
}

namespace Pasteque\Server\API;

class TestAPI implements API {
    const NO_PARAM_RESULT = 10;
    public function __construct($dao) {}
    public static function fromApp($app) { return new TestAPI(null); }
    public function noParam() { return TestAPI::NO_PARAM_RESULT; }
    public function params($a, $b) { return $a + $b; }
}
