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

namespace Pasteque\Server\Model;

/**
 * Use this class instead of \StdClass or associative array when returning
 * something from an API.
 * @package Pasteque
 * @SWG\Definition(type="object")
 */
class GenericModel
{

    protected $properties;
    protected $values;

    public function __construct() {
        $this->properties = [];
        $this->values = [];
    }

    public function set($property, $value) {
        $this->properties[] = $property;
        $this->values[$property] = $value;
    }

    public function get($property) {
        if (array_key_exists($property, $this->values)) {
            return $this->values[$property];
        }
        return null;
    }

    protected function recursiveToStruct($value) {
        if (is_a($value, \Pasteque\Server\System\DAO\DoctrineModel::class)
                || ($value instanceof \Pasteque\Server\Model\GenericModel)) {
            return $value->toStruct();
        } else {
            if (is_array($value)) {
                $output = [];
                foreach($value as $v) {
                    $output[] = $this->recursiveToStruct($v);
                }
                return $output;
            } else {
                return $value;
            }
        }
    }

    public function toStruct() {
        $struct = [];
        foreach ($this->properties as $prop) {
            $value = $this->values[$prop];
            $struct[$prop] = $this->recursiveToStruct($value);
        }
        return $struct;
    }
}
