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

use \Pasteque\Server\Model\Field\FloatField;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class FloatFieldTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testConvertValidFloat() {
        $field = new FloatField('test', ['decimals' => 2]);
        $this->assertEquals(null, $field->convert(null));
        $this->assertEquals(3.2, $field->convert(3.2));
        $this->assertEquals(3.25, $field->convert(3.249));
        $this->assertEquals(3.24, $field->convert(3.244));
        $this->assertEquals(3.0, $field->convert(3));
        $this->assertEquals(3.0, $field->convert('3'));
        $this->assertEquals(3.0, $field->convert(' 3  '));
        $this->assertEquals(3.0, $field->convert('+3.0'));
        $this->assertEquals(-3.0, $field->convert('- 3.0'));
        $this->assertEquals(3.25, $field->convert('3.249'));
        $field2 = new FloatField('test2');
        $this->assertEquals(3.14159, $field2->convert(M_PI));
    }    

    public function testConvertInvalidFloat() {
        $this->expectException(\InvalidArgumentException::class);
        $field = new FloatField('test');
        $field->convert(true);
    }
}
