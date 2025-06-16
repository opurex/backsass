<?php

use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\System\API\APICaller;

/**
 * GET taxGetAllGet
 * Summary:
 * Notes: Get a array of all Tax
 * Output-Formats: [application/json]
 */
$app->GET('/api/tax/getAll', function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'tax', 'getAll'));
});


/**
 * GET taxIdGet
 * Summary:
 * Notes: Get a Tax
 * Output-Formats: [application/json]
 */
$app->GET('/api/tax/{id}', function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'tax', 'get', $args['id']));
});

/** Create or update a tax. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/tax',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $tax = Tax::loadFromId($tab['id'], $ptApp->getDao());
    if ($tax == null) {
        $tax = new Tax();
    }
    try {
        $tax->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'tax', 'write',
            $tax));
});

