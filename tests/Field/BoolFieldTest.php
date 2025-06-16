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

use \Pasteque\Server\Model\Field\BoolField;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class BoolFieldTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testConvertValidBool() {
        $field = new BoolField('test');
        $this->assertEquals(null, $field->convert(null));
        $this->assertEquals(true, $field->convert(true));
        $this->assertEquals(false, $field->convert(false));
        $this->assertEquals(true, $field->convert(1));
        $this->assertEquals(false, $field->convert(0));
        $this->assertEquals(true, $field->convert('True'));
        $this->assertEquals(false, $field->convert('False'));
        $this->assertEquals(true, $field->convert('1'));
        $this->assertEquals(false, $field->convert('0'));
    }    

    public function testConvertInvalidBoolString() {
        $this->expectException(\InvalidArgumentException::class);
        $field = new BoolField('test');
        $field->convert('Not true');
    }

    public function testConvertInvalidBoolNonString() {
        $this->expectException(\InvalidArgumentException::class);
        $field = new BoolField('test');
        $field->convert(3.2);
    }
}
