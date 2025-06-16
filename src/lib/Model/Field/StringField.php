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

/** Field for string values. By convention, string fields are never nullable
 * and use empty string instead. */
class StringField extends Field
{
    public const TYPE = 'string';
    public const CONVERT_CSTR = 0;

    protected $length = 255;

    /**
     * @Override From Field::__construct
     * StringField has no option 'nullable'. Null is converted to ''.
     * It has an dedicated option 'length' (default 255) to specify a
     * maximum length. The value will be truncated. Set to null to disable
     * the length check.
     */
    public function __construct($name, $options = []) {
        parent::__construct($name, $options);
        $this->nullable = false;
        if (array_key_exists('length', $options)) {
            $this->length = $options['length'];
        }
    }

    /**
     * @Override From Field::convert
     * Convert to string representation. Null is converted to an empty string.
     * True and false are converted to 'true' and 'false'.
     */
    public function convert($input) {
        if ($input === null) {
            return '';
        }
        if ($input === false) {
            return 'false';
        }
        if ($input === true) {
            return 'true';
        }
        $val = strval($input);
        if ($this->length !== null) {
            $val = substr($val, 0, $this->length);
        }
        return $val;
    }
}
