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

namespace Pasteque\Server\System\SysModules\Ident;

use Pasteque\Server\System\SysModules\SysModule;

/** Base class for ident modules to be used along the module factory. */
abstract class IdentModule extends SysModule {

    /** Get user information from it's login.
     * @return {"id": "user id", "pwd_hash": "password hash"},
     * null if no user found for the given login. */
    abstract function getUser($login);

}
