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

use \Pasteque\Server\Model\Field\StringField;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class StringFieldTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testConvertValid() {
        $field = new StringField('test');
        $this->assertEquals('abc', $field->convert('abc'));
        $this->assertEquals('true', $field->convert(true));
        $this->assertEquals('3', $field->convert(3));
    }

    public function testConvertLength() {
        $field = new StringField('test', ['length' => 3]);
        $this->assertEquals('123', $field->convert('1234567890'));
    }

    public function testConvertNoLength() {
        $field = new StringField('test', ['length' => null]);
        $pattern = '1234567890';
        $val = $pattern;
        for ($i = 1; $i < 30; $i++) {
            $val .= $pattern;
        }
        $this->assertEquals($val, $field->convert($val));
    }
}
