<?php

use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET server version
 * Summary:
 * Notes:
 * Output-Formats: [application/json]
 */
$app->GET('/api/sync/{cashregister}', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $result = APICaller::run($ptApp, 'sync', 'syncCashRegister',
            $args['cashregister']);
    if ($result->getStatus() == APIResult::STATUS_CALL_REJECTED
            && is_a($result->getContent(), RecordNotFoundException::class)) {
        return $response->notFound($result->getContent(),
                sprintf('No cash register found with name %s',
                        $args['cashregister']));
    }
    return $response->withApiResult($result);
});

$app->GET('/api/sync', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp,
                    'sync', 'sync'));
});
