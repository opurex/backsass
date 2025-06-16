<?php

use Pasteque\Server\System\API\APICaller;

/** Middleware to accept/reject CORS requests. */
$corsMiddleware = function($request, $response, $next) {

    $ptApp = $this->get('settings')['ptApp'];
    // Pass the call to the regular route
    $response = $next($request, $response);
    // Set CORS headers if required
    $headers = APICaller::getCORSHeaders($request->getHeaderLine('Origin'),
            $ptApp->getAllowedOrigin());
    foreach ($headers as $header => $value) {
        $response = $response->withHeader($header, $value);
    }
    return $response;
};
