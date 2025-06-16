<?php
//    Pasteque API
//
//    Copyright (C) 2012-2017 Pasteque contributors
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

namespace Pasteque\Server\CommonAPI;

use \Pasteque\Server\System\Login;

class LoginAPI implements \Pasteque\Server\API\API {

    public function __construct($identModule, $secret) {
        $this->identModule = $identModule;
        $this->secret = $secret;
    }

    public static function fromApp($app) {
        return new self($app->getIdentModule(), $app->getJwtSecret());
    }

    /** Request a token.
     * @return The token if login is a success, null otherwise. */
    public function getToken($login, $password) {
        $user = $this->identModule->getUser($login);
        if (Login::login($user, $password)) {
            return Login::issueToken($login, $this->secret);
        } else {
            return null;
        }
    }
    /** Alias for getToken. */
    public function login($login, $password) {
        return $this->getToken($login, $password);
    }
}
