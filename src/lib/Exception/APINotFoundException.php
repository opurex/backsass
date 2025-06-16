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
 * The requested API was not found.
 */
class APINotFoundException extends \Exception implements PastequeException
{
    private $api;
    private $action;
    private $minArgc;
    private $maxArgc;
    private $givenArgc;

    private function constructAPI($api) {
        $this->api = $api;
        $msg = sprintf("API %s doesn't exist.", $this->getJsonableApi());
        parent::__construct($msg);
    }

    private function constructAction($api, $action) {
        $this->api = $api;
        $this->action = $action;
        $msg = sprintf("API %s->%s doesn't exist.",
                $this->getJsonableApi(), $this->getJsonableAction());
        parent::__construct($msg);
    }

    private function constructArgs($api, $action,
            $minArgc, $maxArgc, $givenArgc) {
        $this->api = $api;
        $this->action = $action;
        $this->minArgc = $minArgc;
        $this->maxArgc = $maxArgc;
        $this->givenArgc = $givenArgc;
        $msg = '';
        if ($minArgc == $maxArgc) {
            $msg = sprintf('API %s->%s expects %d arguments (%d given).',
                    $this->getJsonableAPI(), $this->getJsonableAction(),
                    $this->minArgc, $this->givenArgc);
        } else {
            $msg = sprintf('API %s->%s expects between %d and %s arguments (%d given).',
                    $this->getJsonableAPI(), $this->getJsonableAction(),
                    $this->minArgc, $this->maxArgc, $this->givenArgc);
        }
        parent::__construct($msg); 
    }

    private function constructArgsInvalid($api, $action) {
        $this->api = $api;
        $this->action = $action;
        $msg = sprintf('API %s->%s was called with an invalid number of arguments.',
                    $this->getJsonableAPI(), $this->getJsonableAction());
        parent::__construct($msg);
    }

    public function __construct($api, $action = null,
            $minArgc = null, $maxArgc = null, $givenArgc = null) {
        if ($action === null) {
            $this->constructAPI($api);
        } elseif ($action !== null && ($minArgc === null
                || $maxArgc === null || $givenArgc === null)) {
            $this->constructAction($api, $action);
        } elseif ($minArgc !== null && $maxArgc !== null
                && $givenArgc !== null) {
            $this->constructArgs($api, $action,
                    $minArgc, $maxArgc, $givenArgc);
        } else {
            $this->constructArgsInvalid($api, $action);
        }
    }

    public function getAPI() {
        return $this->api;
    }

    public function getAction() {
        return $this->action;
    }

    public function getMinArgc() {
        return $this->minArgc;
    }

    public function getMaxArgc() {
        return $this->maxArgc;
    }

    public function getGivenArgc() {
        return $this->givenArgc;
    }

    public function getJsonableAPI() {
        if (gettype($this->api) == 'resource') {
            return '<resource>';
        } else {
            return $this->api;
        }
    }

    public function getJsonableAction() {
        if (gettype($this->action) == 'resource') {
            return '<resource>';
        } else {
            return $this->action;
        }
    }

    public function toStruct() {
        return [
            'api' => $this->getJsonableAPI(),
            'action' => $this->getJsonableAction(),
            'minArgc' => $this->minArgc,
            'maxArgc' => $this->maxArgc,
            'givenArgc' => $this->givenArgc,
            'msg' => $this->message,
        ];
    }
}
