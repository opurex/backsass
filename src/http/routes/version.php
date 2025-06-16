<?php

use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET server version
 * Summary:
 * Notes:
 * Output-Formats: [application/json]
 */
$app->GET('/api/version', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'version', 'get'));
});
