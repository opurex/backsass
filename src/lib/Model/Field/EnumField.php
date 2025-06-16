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

/** Enumeration field. Accept only a given set of values. These values can
 * either be strings or integers. Null may be valid. */
class EnumField extends Field
{
    public const TYPE = 'enum';
    public const CONVERT_CSTR = InvalidFieldException::CSTR_ENUM; 

    protected $values = [];

    /**
     * @Override From Field::__construct__
     * @param $options EnumField has the dedicated options:
     * - 'values': an array of accepted values.
     */
    public function __construct($name, $options) {
        parent::__construct($name, $options);
        if (array_key_exists('values', $options)) {
            $this->values = $options['values'];
        }
    }

    /**
     * @Override From Field::convert
     * Convert an input to any of the accepted values. Comparison is
     * done between case-insesitive string representation of the values.
     * For example if 1 (int) is a valid value, "1" is converted to 1.
     */
    public function convert($input) {
        if ($input === null) {
            return null;
        }
        for ($i = 0; $i < count($this->values); $i++) {
            if (strtolower(strval($this->values[$i])) == strtolower(strval($input))) {
                return $this->values[$i];
            } 
        }
        throw new \InvalidArgumentException();
    }
}
