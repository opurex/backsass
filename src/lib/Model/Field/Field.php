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

/** Definition of a field and conversion of type. */
abstract class Field
{
    /** Name of the type. Override this in every subclass. */
    public const TYPE = null;
    /** The InvalidFieldException constraint when conversion fails. */
    public const CONVERT_CSTR = null;

    /** Field name */
    protected $name;
    /** When true, null is a valid value. */
    protected $nullable = false;
    /**
     * When set, the value is automatically set from an other required field.
     * This allows non-nullable fields not to be set.
     */
    protected $autosetFieldName;

    /**
     * Create a Field for an attribute or argument, with options.
     * @param $name The name of the attribute or argument.
     * @param $options Optional dictionary.
     * - 'nullable' (boolean) default false.
     * - 'autosetFrom' (boolean) default null.
     */
    public function __construct($name, $options = []) {
        $this->name = $name;
        if (array_key_exists('nullable', $options)) {
            $this->nullable = $options['nullable'] == true;
        }
        if (array_key_exists('autosetFrom', $options)) {
            if ($options['autosetFrom'] !== false) {
                // autoset === false means autoset = null
                $this->autosetFieldName = $options['autosetFrom'];
            }
        }
    } 

    /**
     * Check that the input can be read as the according field and return it.
     * It does not check field options.
     * @param $input A value to convert.
     * @throws InvalidArgumentException If the input cannot be converted to the
     * type of the field.
     * @return The converted input.
     */
    public abstract function convert($input);

    /**
     * Check that the input can be read as the according field and return it.
     * This is a wrapper to convert($input) with a different exception type.
     * @param $class The name of the class for that field.
     * @param $id The id (or null) of the record for that field.
     * @param $input A value to check and convert.
     * @throws InvalidFieldException If the input cannot be converted to the
     * type of the field.
     */
    public function convertField($class, $id, $input) {
        try {
            return $this->convert($input);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidFieldException(static::CONVERT_CSTR,
                    $class, $this->name, $id, $input);
        }
    }

    /**
     * Check if two values are considered equals for that field.
     * The default implementation is a regular === check.
     * @param $val1 Value to compare. It should be a converted one.
     * @param $val2 Value to compare. It should be a converted one.
     * @return True if the values are considered equal, false otherwise.
     */
    public function areEqual($val1, $val2) {
        return $val1 === $val2;
    }

    public function getName() {
        return $this->name;
    }

    public function isNullable() {
        return $this->nullable;
    }

    public function isAutoset() {
        return $this->autosetFieldName !== null;
    }

    public function getAutosetFieldName() {
        return $this->autosetFieldName;
    }
}

