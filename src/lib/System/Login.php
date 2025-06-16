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

namespace Pasteque\Server\System;

use \Firebase\JWT\JWT;

/** Static class to check and generate JWT. */
class Login {

    /** The header, cookie, get or post param to put the auth token in. */
    const TOKEN_HEADER = 'Token';

    private function __construct() {}

    /** Check for an available token. In order it checks in:
     * header, post, get, cookie. */
    public static function getToken() {
        if (!empty($_SERVER['HTTP_' . strtoupper(static::TOKEN_HEADER)])) {
            return $_SERVER['HTTP_' . strtoupper(static::TOKEN_HEADER)];
        } elseif (!empty($_POST[static::TOKEN_HEADER])) {
            return $_POST[static::TOKEN_HEADER];
        } else if (!empty($_GET[static::TOKEN_HEADER])) {
            return $_GET[static::TOKEN_HEADER];
        } else if (!empty($_COOKIE[static::TOKEN_HEADER])) {
            return $_COOKIE[static::TOKEN_HEADER];
        } else {
            return null;
        }
    }

    /** Check if a token is valid and return user id if true.
     * @param $token The string token to get auth from.
     * @param $secret The secret used to generate the token.
     * @param $ttl Time to live in second of the token.
     * @param $time The current time (default)
     * or a given time for testing.
     * @return User id if the token is valid, null otherwise.
     * @throws \UnexpectedValueException If the token is malformed. */
    public static function getLoggedUserId($token, $secret, $ttl, $time = null) {
        if ($token === null) { throw new \UnexpectedValueException('Token is null'); }
        try {
            $jwt = JWT::decode($token, $secret, array('HS256', 'HS512', 'HS384'));
            if (static::isValid($jwt, $ttl, $time) ){
                return $jwt->user;
            }
            return null;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return null;
        }
    }

    /** Generate a JWT string for a given user. */
    public static function issueToken($login, $secret, $time = null) {
        if ($time == null) { $time = time(); }
        $payload = array('iat' => $time, 'user' => $login);
        return JWT::encode($payload, $secret);
    }

    /** Generate a JWT string for the current user and config. */
    public static function issueAppToken($app) {
        return static::issueToken($app->getCurrentUser()['id'], $app->getJWTSecret());
    }

    /** Check if the given user can login with the given password.
     * This should be used only from login page/api and use tokens once logged.
     * @param $user An user object as returned by an IdentModule
     * @param $password The password input.
     * @return True if the user is authenticated and can continue.
     * false otherwise. */
    public static function login($user, $password) {
        if ($user === null || !isset($user['pwd_hash'])) {
            return false;
        }
        if (password_verify($password, $user['pwd_hash'])) {
            return true;
        }
        return false;
    }

    /** Check if the token is valid from the "iat" field and expiration time.
     * We use iat + timeout instead of exp to prevent cracking a long time
     * token.
     * @param $jwtPayload Associative array representing a payload.
     * @param $timeout The expiration time in seconds.
     * @param $time The current time (default)
     * or a given time for testing purpose. */
    public static function isValid($jwtPayload, $timeout, $time = null) {
        if (isset($jwtPayload->iat)) {
            if ($time === null) { $time = time(); }
            $exp = intVal($jwtPayload->iat) + $timeout;
            return $exp > $time;
        } else {
            // No "issued at" date, consider to be invalid
            return false;
        }
    }

}
