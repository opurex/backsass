<?php
//    Pasteque API
//
//    Copyright (C) 2012-2917 Pasteque contributors
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\System\SysModules;

/** Base class for system modules to be used along the module factory. */
abstract class SysModule {

    /** Dictionary of properties. Values can be a string or a dictionary
     * {"name": "prop name", "default": "default value"}. When no default
     * is given, the property is considered mandatory. */
    protected static $expectedProperties;
    protected $properties;

    /** Get an array of all available properties. */
    protected static function getPropertyList() {
        $allProps = array();
        foreach (static::$expectedProperties as $prop) {
            $allProps[] = (is_array($prop)) ? $prop['name'] : $prop;
        }
        return $allProps;
    }

    /** Build a module with configuration properties.
     * @throws SysModuleConfigException When a required property is missing. */
    public function __construct($properties) {
        $this->properties = array();
        foreach (static::$expectedProperties as $prop) {
            $name = (is_array($prop)) ? $prop['name'] : $prop;
            if (!array_key_exists($name, $properties)) {
                // Prop not set: check default value or throw error
                if (is_array($prop) && array_key_exists('default', $prop)) {
                    $this->properties[$name] = $prop['default'];
                } else {
                    throw new SysModuleConfigException($prop);
                }
            } else {
                $this->properties[$name] = $properties[$name];
            }
        }
    }

    protected function getProperty($name) {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } else if (array_key_exists($name, static::$defaultProperties)) {
            return static::$defaultProperties[$name];
        }
        return null;
    }
}
