<?php
//    Pastèque API
//
//    Copyright (C) 2012-2015 Scil (http://scil.coop)
//    Cédric Houbart, Philippe Pary
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

namespace Pasteque\Server\Exception;

/**
 * Writing a record failed because one of it's fields has an invalid value.
 */
class InvalidFieldException extends \Exception implements PastequeException
{
    /** The field is read only. */
    const CSTR_READ_ONLY = 'ReadOnly';
    /** The value must be unique. */
    const CSTR_UNIQUE = 'UniqueValue';
    /** The field is a reference to an other record but this record
     * was not found. */
    const CSTR_ASSOCIATION_NOT_FOUND = 'AssociationNotFound';
    /** The field is null but it cannot be. */
    const CSTR_NOT_NULL = 'NotNull';
    /** The field is not an array but it must be. */
    const CSTR_ARRAY = 'ArrayRequired';
    /** The field is a reference to a cash session but this session
     * must be opened. */
    const CSTR_OPENED_CASH = 'OpenedCashRequired';
    /**
     * The field marks the record as the default one and there would be no
     * default anymore.
     */
    const CSTR_DEFAULT_REQUIRED = 'DefaultRequired';
    /** The field is an enum and the value is not within it. */
    const CSTR_ENUM = 'Enum';
    /** The value cannot be converted to float. */
    const CSTR_FLOAT = 'Float';
    /** The value cannot be converted to boolean. */
    const CSTR_BOOL = 'Boolean';
    /** The value cannot be converted to integer. */
    const CSTR_INT = 'Integer';
    const CSTR_INVALID_DATE = 'InvalidDate';
    const CSTR_INVALID_DATERANGE = 'InvalidDateRange';

    private $constraint;
    private $class;
    private $field;
    private $id;
    private $value;

    public function __construct($constraint, $class, $field, $id, $value) {
        $this->constraint = $constraint;
        $this->class = $class;
        $this->field = $field;
        $this->id = $id;
        $this->value = $value;
        parent::__construct();
    }

    public function getConstraint() {
        return $this->constraint;
    }

    public function getClass() {
        return $this->class;
    }

    public function getField() {
        return $this->field;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        if ($this->id == null) {
            $this->id = $id;
        }
    }

    public function getValue() {
        return $this->value;
    }

    public function getJsonableId() {
        if (gettype($this->id) == 'resource') {
            return '<resource>';
        } else {
            return $this->id;
        }
    }

    public function getJsonableValue() {
        if (gettype($this->value) == 'resource') {
            return '<resource>';
        } else {
            return $this->value;
        }
    }

    public function toStruct() {
        $class = $this->class;
        if ($class !== null && $class[0] == '\\') {
            $class = substr($class, 1);
        }
        return [
            'error' => 'InvalidField',
            'constraint' => $this->constraint,
            'class' => $class,
            'field' => $this->field,
            'key' => $this->getJsonableId(),
            'value' => $this->getJsonableValue(),
        ];
    }
}
