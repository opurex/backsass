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

/** Integer field. Null may be valid. */
class IntField extends Field
{
    public const TYPE = 'int';
    public const CONVERT_CSTR = InvalidFieldException::CSTR_INT;

    protected $rounding = false;

    /**
     * @Override From Field::_construct
     * IntField has an extra option
     * - 'rounding': when true, accept float values and round them
     * (default false).
     */
    public function __construct($name, $options = []) {
        parent::__construct($name, $options);
        if (array_key_exists('rounding', $options)) {
            $this->rounding = $options['rounding'];
        }
    }
    /**
     * @Override From Field::convert
     * Convert an input to an int. Null is accepted and returned as is.
     * A string is parsed with spaces removed, an empty string is converted
     * to null. Decimal values are not accepted unless .0.
     */
    public function convert($input) {
        if ($input === null || is_int($input)) {
            return $input;
        }
        if (is_string($input)) {
            $str = str_replace(' ', '', $input);
            if ($str == '') {
                return null;
            }
            if ($this->rounding) {
                if (preg_match('/^[+-]?[0-9]*(.[0-9]+)?$/', $str)) {
                    return intval(round(floatval($str)));
                } elseif (preg_match('/^[+-]?[0-9]*e[0-9]+/', $str)) {
                    return intval(round(floatval($str)));
                }
            } else {
                if (preg_match('/^[+-]?[0-9]*(.[0]+)?$/', $str)) {
                    return intval($str);
                }
            }
        }
        if (is_float($input)) {
            if ($this->rounding) {
                return intval(round($input));
            } else {
                if (abs(round($input) - $input) <= 0.000001) {
                    return intval(round($input));
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
