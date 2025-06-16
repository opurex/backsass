<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
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

namespace Pasteque\Server\System\API;

/** Utility class for APICaller to format things. */
class APIMethodParser {

    /** @var \Pasteque\Server\API\API */
    private $api;
    /** @var \ReflectionMethod */
    private $method;

    /**
     * Build a parser.
     * @param \Pasteque\Server\API\API $api Instance of the API class.
     * @param \ReflectionMethod $method Method requested to call.
     * to pass to the method.
     */
    public function __construct($api, $method) {
        $this->api = $api;
        $this->method = $method;
    }

    /**
     * Check that the number of arguments is suitable for the method.
     * @param array $args Flat or associative array of arguments.
     * @return True if the size or $args is suitable for the targeted method.
     */
    public function checkArgc($args) {
        $reqParamCount = $this->method->getNumberOfRequiredParameters();
        $allParamCount = $this->method->getNumberOfParameters();
        $argc = count($args);
        return ($argc >= $reqParamCount && $argc <= $allParamCount);
    }

    private function isFlatArray($args) {
        $keys = array_keys($args);
        for ($i = 0; $i < count($args); $i++) {
            if (!$keys[$i] == $i) { return false; }
        }
        return true;
    }

    /**
     * Get an array of arguments to pass to call the method.
     * Arguments are considered passing the $this->checkArgc() test.
     * @param array $args Associative of flat array
     * to conform to the targeted method signature
     * @return array A flat array to use to call the method.
     * @throws \BadMethodCallException if some named arguments
     * are found but not all.
     */
    public function buildArgsArray($args) {
        if ($this->isFlatArray($args)) { return $args; }
        // Args is an associative array, look for named arguments
        $params = $this->method->getParameters();
        $flatArgs = [];
        for ($i = 0; $i < count($params); $i++) {
            $param = $params[$i];
            $paramName = $param->getName();
            if (array_key_exists($paramName, $args)) {
                // Found, push the named argument
                $flatArgs[] = $args[$paramName];
            } else if ($param->isOptional()) {
                // Not found but optional, put default value
                $flatArgs[] = $param->getDefaultValue();
            } else {
                // Named parameter not found
                throw new \BadMethodCallException(sprintf('Missing named argument %s', $paramName));
            }
        }
        return $flatArgs;
    }

    public function getAPI() { return $this->api; }
    public function getMethod() { return $this->method; }
    /** @return int The number of required parameters. */
    public function getMinArgc() {
        return $this->method->getNumberOfRequiredParameters();
    }
    /** @return int The number of parameters including optional ones. */
    public function getMaxArgc() {
        return $this->method->getNumberOfParameters();
    }
}
