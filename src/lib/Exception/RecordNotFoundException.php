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
 * A record was requested through it's id but nothing was found.
 */
class RecordNotFoundException extends \Exception implements PastequeException
{
    private $class;
    private $id;

    public function __construct($class, $id) {
        $this->class = $class;
        $this->id = $id;
        parent::__construct();
    }

    public function getClass() {
        return $this->class;
    }

    public function getId() {
        return $this->id;
    }

    public function getJsonableId() {
        if (gettype($this->id) == 'resource') {
            return '<resource>';
        } else {
            return $this->id;
        }
    }

    public function toStruct() {
        $class = $this->class;
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        return [
            'error' => 'RecordNotFound',
            'class' => $class,
            'key' => $this->getJsonableId(),
        ];
    }
}
