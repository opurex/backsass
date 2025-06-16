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

class SysModuleFactory {

    private static function parseName($name) {
        $ret = str_replace('..', '', $name);
        return strtolower($ret);
    }

    public static function getIdentModule($name, $config) {
        $name = SysModuleFactory::parseName($name);
        switch ($name) {
        case 'inifile':
            return new Ident\InifileIdent($config);
        case 'single':
            return new Ident\SingleIdent($config);
        default:
            throw new SysModuleNotFoundException(sprintf('ident/%s', $name));
        }
    }

    public static function getDatabaseModule($name, $config) {
        $name = SysModuleFactory::parseName($name);
        switch ($name) {
        case 'inifile':
            return new Database\InifileDB($config);
        case 'single':
            return new Database\SingleDB($config);
        default:
            throw new SysModuleNotFoundException(sprintf('database/%s', $name));
        }
    }

    /** Factorized function for extract*ModuleConfig. */
    private static function extractModuleConfig($config, $type, $name) {
        $name = SysModuleFactory::parseName($name);
        $subcfg = array();
        $prefix = sprintf('%s/%s/', $type, $name);
        $strlen = strlen($prefix);
        foreach ($config as $key => $value) {
            if (substr($key, 0, $strlen) == $prefix) {
                $subcfg[substr($key, $strlen)] = $value;
            }
        }
        return $subcfg;
    }

    /** Parse a configuration dictionary to extract only the relevant entries
     * for the requested module.
     * These entries are formated like ident/<$name>/<entry>.
     * @param $config A global config dictionary.
     * @param $name The name of the module.
     * @return A new config dictionary with only and reformated entries. */
    public static function extractIdentModuleConfig($config, $name) {
        return SysModuleFactory::extractModuleConfig($config, 'ident', $name);
    }

    /** Parse a configuration dictionary to extract only the relevant entries
     * for the requested module.
     * These entries are formated like database/<$name>/<entry>.
     * @param $config A global config dictionary.
     * @param $name The name of the module.
     * @return A new config dictionary with only and reformated entries. */
    public static function extractDatabaseModuleConfig($config, $name) {
        return SysModuleFactory::extractModuleConfig($config, 'database', $name);
    }
}
