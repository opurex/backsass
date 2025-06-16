<?php
///    Pastèque Web back office, Single ident module
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

namespace Pasteque\Server\System\SysModules\Database;

/** Read database info from configuration. 
 * Requires "type", "host", "name", "user", "password" to define
 * database url and credentials. */
class SingleDB extends DBModule {

    protected static $expectedProperties = ['type', 'host', 'port',
            'name', 'user', 'password'];

    public function getDatabase($login) {
        return ['type' => $this->getProperty('type'),
                'host' => $this->getProperty('host'),
                'port' => $this->getProperty('port'),
                'name' => $this->getProperty('name'),
                'user' => $this->getProperty('user'),
                'password' => $this->getProperty('password')];
    }
}
