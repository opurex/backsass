<?php

use Pasteque\Server\System\API\APICaller;
use Pasteque\Server\System\API\APIResult;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Pasteque\Server\System\Login;

/**
 * POST login.
 * Summary:
 * Notes: Get the JWT auth token.
 * Output-Formats: [application/json]
 */
$app->POST('/api/login', function ($request, $response, $args) {
    $queryParams = $request->getQueryParams();
    $data = $request->getParsedBody();
    $argsApi = [
            'login' => (!empty($data['user'])) ? $data['user'] : '',
            'password' => (!empty($data['password'])) ? $data['password'] : ''
    ];
    $ptApp = $this->get('settings')['ptApp'];
    // Log the user in if the call is a success
    $apiResult = APICaller::run($ptApp, 'login', 'login', $argsApi);
    if ($apiResult->getStatus() == APIResult::STATUS_CALL_OK) {
        // Set the cookie
        $basePath = $request->getUri()->getBasePath();
        if ($basePath == '') {
            $basePath = '/';
        }
        $newToken = $apiResult->getContent();
        $response = $response->withHeader(Login::TOKEN_HEADER, $newToken);
        $cookie = SetCookie::create(Login::TOKEN_HEADER)
            ->withValue($newToken)
            ->withMaxAge($ptApp->getJwtTimeout())
            ->withPath($basePath)
            ->withDomain($request->getUri()->getHost());
        $response = FigResponseCookies::set($response, $cookie);
    }
    return $response->withAPIResult($apiResult);
});
