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

use \Pasteque\Server\Exception\APINotFoundException;
use \Pasteque\Server\Exception\PastequeException;
use \Pasteque\Server\System\Login;

/** Utility class to call API methods. */
class APICaller {

    /**
     * Escape and format the API name to be safely called and match the API
     * filename convention.
     * '..' are removed, first letter is uppercased while the other are
     * lowercased. The 'API' suffix is added/uppercased if required.
     * @param string $apiName The name of the API.
     * @return string The formated name of the API to match it's class name.
     */
    public static function formatAPIName($apiName) {
        $name = strtolower($apiName);
        $name = str_replace('..', '', $name);
        if (substr($name, -3) == 'api') {
            $name = substr($name, 0, -3);
        }
        $name = ucfirst($name);
        return sprintf('%sAPI', $name);
    }

    /**
     * Run an API method and wrap the response in an APIResult.
     * @param \Pasteque\Server\AppContext $app The application context.
     * @param string $apiName The name of the API to call. It is formated with
     * formatAPIName().
     * @param string $methodName The name of the method to call within the API.
     * @param array $args The arguments to pass to the method.
     * @return \Pasteque\Server\System\API\APIResult An APIResult holding the
     * result of the method. The content is the result of the method for
     * successful calls, or an exception for rejected calls or errors.
     */
    public static function run($app, $apiName, $methodName, $args = array()) {
        if ($args === null) { $args = array(); }
        if (!is_array($args)) { $args = array($args); }
        // Get API class
        $apiName = APICaller::formatAPIName($apiName);
        $className = sprintf('\Pasteque\Server\CommonAPI\%s', $apiName);
        if (!class_exists($className, true)) {
            $apiPkg = $app->isFiscalMirror() ? 'FiscalMirrorAPI' : 'API';
            $className = sprintf('\Pasteque\Server\%s\%s', $apiPkg, $apiName);
            if (!class_exists($className, true)) {
                $className = null;
            }
        }
        if ($className == null) {
            return APIResult::reject(new APINotFoundException($apiName));
        }
        // Get method
        $apiClass = new \ReflectionClass($className);
        if (!$apiClass->implementsInterface('\Pasteque\Server\API\API')) { 
            return APIResult::reject(new APINotFoundException($apiName));
        }
        $constructor = $apiClass->getMethod('fromApp');
        $api = $constructor->invoke(null, $app);
        if (!method_exists($api, $methodName)) {
            return APIResult::reject(new APINotFoundException($apiName,
                    $methodName));
        }
        $method = new \ReflectionMethod($api, $methodName);
        $methodParser = new APIMethodParser($api, $method);
        // Check arguments
        if (!$methodParser->checkArgc($args)) {
            return APIResult::reject(new APINotFoundException($apiName,
                    $methodName,
                    $methodParser->getMinArgc(),
                    $methodParser->getMaxArgc(),
                    count($args)));
        }
        // Invoke
        try {
            $realArgs = $methodParser->buildArgsArray($args);
            return APIResult::success($method->invokeArgs($api,
                    $realArgs));
        } catch (PastequeException $e) {
            return APIResult::reject($e);
        } catch (\BadMethodCallException $e) {
            return APIResult::reject($e->getMessage());
        } catch (\UnexpectedValueException $e) {
            return APIResult::reject($e->getMessage());
        } catch (\ReflectionException $e) {
            $app->getLogger()->error('Internal error while calling API',
                    array('exception' => $e));
            return APIResult::error($e->__toString());
        } catch (\Exception $e) {
            $app->getLogger()->error('Internal error',
                    array('exception' => $e));
            return APIResult::error($e->__toString());
        }
    }

    /** Check if the user is authenticated and has permission.
     * @param $userId The user requesting the call.
     * @param $apiName Targeted API name
     * @param $method Targeted API method. */
    public static function checkPermission($userId, $apiName, $method) {
        $apiName = APICaller::formatAPIName($apiName);
        if ($userId == null) {
            // Accept only Login->login for unauthenticated users
            return ($apiName == 'LoginAPI'
                && ($method == 'getToken' || $method == 'login'));
        } else {
            // This is where permission checking will be done
            return true;
        }
    }

    public static function isAllowedOrigin($origin, $allowedOrigins) {
        // Accept *, single value match or in array
        return ($allowedOrigins === '*'
                || (!is_array($allowedOrigins) && $origin === $allowedOrigins)
                || (is_array($allowedOrigins) && in_array($origin, $allowedOrigins)));
    }

    /** Get HTTP headers to allow a cross origin request. */
    public static function getCORSHeaders($origin, $allowedOrigins, $maxAge = 86400) {
        $headers = [];
        if (!static::isAllowedOrigin($origin, $allowedOrigins)) {
            return $headers;
        }
        $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
        $headers['Access-Control-Allow-Origin'] = $origin;
        $headers['Access-Control-Allow-Credentials'] = false;
        $headers['Access-Control-Max-Age'] = $maxAge;
        $headers['Access-Control-Expose-Headers'] = Login::TOKEN_HEADER;
        $headers['Access-Control-Allow-Headers'] = Login::TOKEN_HEADER . ', Content-Type';
        if (is_array($allowedOrigins)) { $headers['Vary'] = 'Origin'; }
        return $headers;
    }

}
