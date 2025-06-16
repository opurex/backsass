<?php

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Pasteque\Server\System\Login;

/** Middleware to reject all calls except login API without a valid token
 * and add a new token to the response header. */
$loginMiddleware = function($request, $response, $next) {

    $ptApp = $this->get('settings')['ptApp'];

    // Check for logged user
    $userId = null;
    $token = Login::getToken();
    if ($token != null) {
        $userId = Login::getLoggedUserId($token, $ptApp->getJwtSecret(), $ptApp->getJwtTimeout());
        if ($userId !== null) {
            $user = $ptApp->getIdentModule()->getUser($userId);
            $ptApp->login($user);
        }
    }

    $path = $request->getUri()->getPath();
    if (substr($path, 0, 1) == '/') {
        $path = substr($path, 1);
    }
    $mustLogin = ('fiscal/' !== substr($path, 0, 7) && 'api/login' !== $path
            && '' !== $path && 'passwordupd/' !== $path && !$request->isOptions());

    if ($userId === null && $mustLogin) {
        // Reject the call because not authenticated.
        $response = $response->withStatus(403, 'Not logged');
    } else {
        // Pass the call to the regular route
        $response = $next($request, $response);
        $basePath = $request->getUri()->getBasePath();
        if ($basePath == '') {
            $basePath = '/';
        }
        // Inject fresh token
        if ($userId !== null) {
            if ($path != 'fiscal/disconnect') {
                $newToken = Login::issueAppToken($ptApp);
                $response = $response->withHeader(Login::TOKEN_HEADER, $newToken);
                $cookie = SetCookie::create(Login::TOKEN_HEADER)
                    ->withValue($newToken)
                    ->withMaxAge($ptApp->getJwtTimeout())
                    ->withPath($basePath)
                    ->withDomain($request->getUri()->getHost());
                $response = FigResponseCookies::set($response, $cookie);
            } else {
                $cookie = SetCookie::create(Login::TOKEN_HEADER)
                    ->withValue("")
                    ->withExpires(1)
                    ->withPath($basePath)
                    ->withDomain($request->getUri()->getHost());
                $response = FigResponseCookies::set($response, $cookie);
            }
        } else {
            // Case of login without prior token. See login route which sets the cookie.
            // Nothing to do here.
        }
    }
    return $response;
};
