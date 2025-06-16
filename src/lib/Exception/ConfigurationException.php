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
 * The configuration is incorrect.
 */
class ConfigurationException extends \Exception implements PastequeException
{
    private $key;
    private $value;

    public function __construct($key, $value, $message) {
        $this->key = $key;
        $this->value = $value;
        $this->message = $message;
        parent::__construct();
    }

    public function getKey() {
        return $this->key;
    }

    public function getValue() {
        return $this->value;
    }

    public function toStruct() {
        return [
            'error' => 'Configuration',
            'key' => $this->getKey(),
            'value' => $this->getValue(),
            'message' => $this->getMessage()
        ];
    }
}
