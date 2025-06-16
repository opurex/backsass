<?php
//    Pastèque Web back office, Single ident module
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

/** Read authentication data from configuration.
 * Requires "db_dsn", "db_username" and "db_password".
 * The database must have a table named pasteque_users
 * (user_id, can_login, password). */
class SingleIdent extends IdentModule {

    protected static $expectedProperties = ['login', 'password'];

    public function getUser($login) {
        if ($login == $this->getProperty('login')) {
            return ['id' => $login, 'pwd_hash' => $this->getProperty('password')];
        }
        return null;
    }
}
