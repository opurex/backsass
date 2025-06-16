<?php
//    Pastèque API
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\Model\Field;

use \Pasteque\Server\Exception\InvalidFieldException;

/** Float field. By convention and for rounding issues, all float values are
 * rounded to a fixed decimal (5 by default). Null may be valid.
 * WARNING: this class was made with 5 and 2 decimal precision. Precision above
 * 5 may fail.
 */
class FloatField extends Field
{
    public const TYPE = 'float';
    public const CONVERT_CSTR = InvalidFieldException::CSTR_FLOAT;

    protected $decimals = 5;

    /**
     * Parse a string as a float.
     * @return A float value or false.
     */
    public static function convertString($input) {
        return false;
    }

    /**
     * @Override From Field::__construct
     * Float fields have extra options:
     * - 'decimals': the fixed number of decimals. Default is 5.
     * Use null to disable rounding.
     */
    public function __construct($name, $options = []) {
        parent::__construct($name, $options);
        if (array_key_exists('decimals', $options)) {
            $this->decimals = $options['decimals'];
        }
    }

    /**
     * @Override From Field::convert
     * Convert an input to an float. Null is accepted and returned as is.
     * A string is parsed with spaces removed, an empty string is converted
     * to null. Decimal values are rounded to a fixed number of decimals.
     */
    public function convert($input) {
        if ($input === null) {
            return $input;
        }
        $conv = false;
        if (is_float($input)) {
            $conv = $input;
        }
        if (is_int($input)) {
            $conv = floatval($input);
        }
        if (is_string($input)) {
            $str = str_replace(' ', '', $input);
            if ($str == '') {
                return null;
            }
            if (preg_match('/^[+-]?[0-9]*(.[0-9]+)?$/', $str)) {
                $conv = floatval($str);
            } elseif (preg_match('/^[+-]?[0-9]*e[0-9]+/', $str)) {
                $conv = floatval($str);
            }
        }
        if ($conv === false) {
            throw new \InvalidArgumentException();
        }
        if ($this->decimals !== null) {
            return round($conv, $this->decimals);            
        }
    }

    /**
     * @Override From Field::areEqual
     * Check equality up to 5 digits precision, with null check.
     */
    public function areEqual($val1, $val2) {
        if ($val1 === null) {
            return $val2 === null;
        } elseif ($val2 === null) {
            return $val1 === null;
        } else {
            return (abs($val1-$val2) < 0.00001);
        }
    }
}
