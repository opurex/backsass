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

use \Pasteque\Server\Model\Field\DateField;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class DateFieldTest extends TestCase {

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    // DateField relies heavily upon DateUtils, which is already tested.

    public function testConvertValidDate() {
        $field = new DateField('test');
        $this->assertNull($field->convert(null));
        $date = $field->convert('2021-05-18');
        $this->assertEquals('2021-05-18', $date->format('Y-m-d'));
    }    

    public function testConvertInvalidDate() {
        $this->expectException(\InvalidArgumentException::class);
        $field = new DateField('test');
        $field->convert('notadate');
    }
}
