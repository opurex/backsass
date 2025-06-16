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

/** Field for boolean values. Null may be valid. */
class BoolField extends Field
{
    public const TYPE = 'bool';
    public const CONVERT_CSTR = InvalidFieldException::CSTR_BOOL;

    /**
     * @Override From Field::convert
     * Convert to boolean. Any integer values outside 0 is considered true,
     * 0 is false. 'true' and 'false' ignoring case are converted.
     * String representation of integers are accepted.
     */
    public function convert($input) {
        if ($input === null || is_bool($input)) {
            return $input;
        }
        if (is_int($input)) {
            return $input != 0;
        }
        if (is_string($input)) {
            $str = str_replace(' ', '', $input);
            if (strtolower($str) == 'true') {
                return true;
            }
            if (strtolower($str) == 'false') {
                return false;
            }
            if (preg_match('/^[+-]?[0-9]+$/', $str)) {
                $conv = intval($str);
                return $conv != 0;
            }
        }
        throw new \InvalidArgumentException();
    }
}
