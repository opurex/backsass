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

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\InvalidRecordException;
use \Pasteque\Server\Exception\PastequeException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\System\DateUtils;
use \PHPUnit\Framework\TestCase;

abstract class PastequeTestCase extends TestCase
{
    protected function checkEquality($a, $b) {
        if (is_a($a, \DateTime::class)) {
            $a = $a->format('Y-m-d H:i:s');
        }
        if (is_a($b, \DateTime::class)) {
            $b = $b->format('Y-m-d H:i:s');
        }
        $this->assertEquals($a, $b);
    }

    public function assertInvalidFieldException($cstr, $class, $field, $key,
            $value, $e) {
        if (is_a($e, PastequeException::class)) {
            $e = $e->toStruct();
        }
        $this->assertEquals('InvalidField', $e['error']);
        $this->assertEquals($class, $e['class']);
        $this->assertEquals($field, $e['field']);
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->checkEquality($v, $e['key'][$k]);
            }
        } else {
            $this->checkEquality($key, $e['key']);
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->checkEquality($v, $e['value'][$k]);
            }
        } else {
            $this->checkEquality($value, $e['value']);
        }
    }

    public function assertRecordNotFoundException($class, $id, $e) {
        if (is_a($e, PastequeException::class)) {
            $e = $e->toStruct();
        }
        $this->assertEquals('RecordNotFound', $e['error']);
        $this->assertEquals($class, $e['class']);
        if (is_array($id)) {
            foreach ($id as $k => $v) {
                $this->checkEquality($v, $e['key'][$k]);
            }
        } else {
            $this->checkEquality($id, $e['key']);
        }
    }

    public function assertConfigurationException($key, $value, $message, $e) {
        if (is_a($e, PastequeException::class)) {
            $e = $e->toStruct();
        }
        $this->assertEquals('Configuration', $e['error']);
        $this->assertEquals($key, $e['key']);
        $this->assertEquals($value, $e['value']);
        $this->assertEquals($message, $e['message']);
    }
}
