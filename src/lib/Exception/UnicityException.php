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
 * The operation failed because the value of a field is required
 * to be unique.
 * Id fields are excluded, those are not business-related.
 */
class UnicityException extends \Exception implements PastequeException
{
    private $class;
    private $field;
    private $value;

    public function __construct($class, $field, $value) {
        $this->class = $class;
        $this->field = $field;
        $this->value = $value;
        $msg = sprintf('The value of %s for %s must be unique (faulty value is %s of type %s).',
                $this->field, $this->class, $this->getJsonableValue(),
                gettype($value));
        parent::__construct($msg);
    }

    public function getClass() {
        return $this->class;
    }

    public function getField() {
        return $this->field;
    }

    public function getValue() {
        return $this->value;
    }

    public function getJsonableValue() {
        if (gettype($this->value) == 'resource') {
            return '<resource>';
        } else {
            return $this->value;
        }
    }

    public function toStruct() {
        return [
            'error' => 'UnicityException',
            'class' => $this->class,
            'field' => $this->field,
            'value' => $this->getJsonableValue(),
            'message' => $this->message,
        ];
    }
}
