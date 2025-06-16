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

use \Pasteque\Server\Model\Field\EnumField;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class EnumFieldTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testConvertValidIntEnum() {
        $field = new EnumField('test', ['values' =>[2, 3]]);
        $this->assertEquals(null, $field->convert(null));
        $this->assertEquals(2, $field->convert(2));
        $this->assertEquals(2, $field->convert('2'));
    }    

    public function testConvertValidStringEnum() {
        $field = new EnumField('test', ['values' =>['a', 'b', '2']]);
        $this->assertEquals(null, $field->convert(null));
        $this->assertEquals('a', $field->convert('a'));
        $this->assertEquals('a', $field->convert('A'));
        $this->assertEquals('2', $field->convert(2));
    }

    public function testConvertInvalidIntEnum() {
        $this->expectException(\InvalidArgumentException::class);
        $field = new EnumField('test', ['values' => [2, 3]]);
        $field->convert(1);
    }
}
