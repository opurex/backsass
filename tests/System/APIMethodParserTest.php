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

use \Pasteque\Server\System\API\APIMethodParser;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class APIMethodParserTest extends TestCase {

    private $api;

    protected function setUp(): void {
        $apiClass = new \ReflectionClass('\Pasteque\Server\APITest');
        $this->api = $apiClass->newInstance();
    }

    protected function tearDown(): void {
    }

    public function testConstruct() {
        $method = new \ReflectionMethod($this->api, 'noParam');
        $parser = new APIMethodParser($this->api, $method);
        $this->assertEquals($this->api, $parser->getAPI());
        $this->assertEquals($method, $parser->getMethod());
    }

    /** @depends testConstruct */
    public function testCheckArgc() {
        $args0 = array();
        $args1 = array(1);
        $args2 = array(1, 2);
        $args3 = array(1, 2, 3);
        $args4 = array(1, 2, 3, 4);
        $args5 = array(1, 2, 3, 4, 5);
        $parserNo = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'noParam'));
        $parserReq = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'reqParams'));
        $parserOpt = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'optParams'));
        $parserMix = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'mixedParams'));
        $this->assertTrue($parserNo->checkArgc($args0));
        $this->assertFalse($parserNo->checkArgc($args1));
        $this->assertFalse($parserNo->checkArgc($args2));
        $this->assertFalse($parserNo->checkArgc($args3));
        $this->assertFalse($parserNo->checkArgc($args4));
        $this->assertFalse($parserNo->checkArgc($args5));
        $this->assertFalse($parserReq->checkArgc($args0));
        $this->assertFalse($parserReq->checkArgc($args1));
        $this->assertTrue($parserReq->checkArgc($args2));
        $this->assertFalse($parserReq->checkArgc($args3));
        $this->assertFalse($parserReq->checkArgc($args4));
        $this->assertFalse($parserReq->checkArgc($args5));
        $this->assertTrue($parserOpt->checkArgc($args0));
        $this->assertTrue($parserOpt->checkArgc($args1));
        $this->assertTrue($parserOpt->checkArgc($args2));
        $this->assertFalse($parserOpt->checkArgc($args3));
        $this->assertFalse($parserOpt->checkArgc($args4));
        $this->assertFalse($parserOpt->checkArgc($args5));
        $this->assertFalse($parserMix->checkArgc($args0));
        $this->assertFalse($parserMix->checkArgc($args1));
        $this->assertTrue($parserMix->checkArgc($args2));
        $this->assertTrue($parserMix->checkArgc($args3));
        $this->assertTrue($parserMix->checkArgc($args4));
        $this->assertFalse($parserMix->checkArgc($args5));
    }

    /** @depends testConstruct */
    public function testBuildArgsArrayEmpty() {
        $parser = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'mixedParams'));
        $args = array();
        $builtArgs = $parser->buildArgsArray($args);
        $this->assertTrue(is_array($builtArgs));
        $this->assertEquals(0, count($builtArgs));
    }

    /** @depends testConstruct */
    public function testBuildArgsArrayFlat() {
        $parser = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'mixedParams'));
        $args = array(3, 4);
        $builtArgs = $parser->buildArgsArray($args);
        $this->assertEquals(2, count($builtArgs));
        $this->assertEquals($args[0], $builtArgs[0]);
        $this->assertEquals($args[1], $builtArgs[1]);
    }

    /** @depends testConstruct */
    public function testBuildArgsArrayAssoc() {
        $parser = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'mixedParams'));
        $args = array('b' => 4, 'a' => 3);
        $builtArgs = $parser->buildArgsArray($args);
        $this->assertEquals(4, count($builtArgs));
        $this->assertEquals($args['a'], $builtArgs[0]);
        $this->assertEquals($args['b'], $builtArgs[1]);
        $this->assertEquals(1, $builtArgs[2]);
        $this->assertEquals(2, $builtArgs[3]);
    }

    /** @depends testConstruct */
    public function testBuildArgsArrayAssocError() {
        $this->expectException(\BadMethodCallException::class);
        $parser = new APIMethodParser($this->api,
            new \ReflectionMethod($this->api, 'mixedParams'));
        $args = array('b' => 4, 'c' => 3);
        $builtArgs = $parser->buildArgsArray($args);
    }
}

class APITest {

    public function noParam() {}
    public function reqParams($a, $b) {}
    public function optParams($a = 1, $b = 2) {}
    public function mixedParams($a, $b, $c = 1, $d = 2) {}
}
