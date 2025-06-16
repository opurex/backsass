<?php
//    Pastèque Web back office, Ini File ident module
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

namespace Pasteque\Server\System\SysModules\Ident;

/** Read authentication data from ini files.
 * Requires "path" to define the directory in which the ini files are stored.
 * The "path" is relative to Pasteque directory if it starts by a dot.
 * Ini files are named <login>_id.ini with special characters replaced:
 * '.' => '_dot_', '/' => '_slh_', '\' => '_aslh_', ' ' => '_sp_'.
 * The ini files contains a 'password' to store the password hash. */
class InifileIdent extends IdentModule {

    protected static $expectedProperties = array(
           array("name" => "path", "default" => "./auth")
    );

    function getUser($login) {
        $path = $this->getProperty('path');
        if (substr($path, 0, 1) == '.') {
            $dir = PT::$ABSPATH . '/' . $path;
        } else {
            $dir = $path;
        }
        $sanitizedUser = str_replace('.', '_dot_', $login);
        $sanitizedUser = str_replace('/', '_slh_', $sanitizedUser);
        $sanitizedUser = str_replace('\\', '_aslh_', $sanitizedUser);
        $sanitizedUser = str_replace(' ', '_sp_', $sanitizedUser);
        $file = $dir . '/' . $sanitizedUser . '_id.ini';
        if (is_readable($file)) {
            $data = parse_ini_file($file);
            // Check password against hash from ini file, kill if wrong
            if (isset($data['password'])) {
                return array('id' => $login, 'pwd_hash' => $data['password']);
            } else {
                // No password set
                return null;
            }
        } else {
            // No ini file found or not readable
            return null;
        }
    }
}
