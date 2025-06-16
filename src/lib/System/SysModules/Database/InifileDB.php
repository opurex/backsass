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

namespace Pasteque\Server\System\SysModules\Database;

/** Read database info from ini files.
 * Requires "path" to define the directory in which the ini files are stored.
 * The "path" is relative to Pasteque directory if it starts by a dot.
 * Ini files are named <login>_db.ini with special characters replaced:
 * '.' => '_dot_', '/' => '_slh_', '\' => '_aslh_', ' ' => '_sp_'.
 * The ini files contains 'type', 'host', 'name', 'user', 'password'
 * to store database url and credentials. */
class InifileDB extends DBModule {

    protected static $expectedProperties = array(
           array("name" => "path", "default" => "./auth")
    );

    public function getDatabase($login) {
        if (substr($this->getProperty('path'), 0, 1) == '.') {
            $dir = PT::$ABSPATH . '/' . $this->getProperty('path');
        } else {
            $dir = $this->getProperty('path');
        }
        $sanitizedUser = str_replace('.', '_dot_', $login);
        $sanitizedUser = str_replace('/', '_slh_', $sanitizedUser);
        $sanitizedUser = str_replace('\\', '_aslh_', $sanitizedUser);
        $sanitizedUser = str_replace(' ', '_sp_', $sanitizedUser);
        $file = $dir . '/' . $sanitizedUser . '_db.ini';
        if (is_readable($file)) {
            $data = parse_ini_file($file);
            // Mandatory values
            if (empty($data['type'])) {
                return false;
            }
            if ($data['type'] !== 'sqlite' && empty($data['host'])) { return false; }
            // Set default values
            if (empty($data['host'])) {
                $data['host'] = null; // For sqlite
            }
            if (empty($data['port'])) {
                switch ($data['type']) {
                case 'mysql': $data['port'] = 3306; break;
                case 'postgresql': $data['port'] = 5432; break;
                case 'sqlite': $data['port'] = null; break;
                default: return false;
                }
            }
            if (empty($data['name'])) { $data['name'] = $sanitizedUser; }
            if (empty($data['user'])) { $data['user'] = $sanitizedUser; }
            if (empty($data['password'])) { $data['password'] = null; }
            return array('type' => $data['type'], 'host' => $data['host'],
                    'port' => $data['port'], 'name' => $data['name'],
                    'user' => $data['user'], 'password' => $data['password']);
        } else {
            // No ini file found or not readable
            return false;
        }
    }
}
