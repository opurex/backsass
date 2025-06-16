<?php

use \Pasteque\Server\Model\Option;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

$app->GET('/api/option/getAll', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $optCall = APICaller::run($ptApp, 'option', 'getAll');
    return $response->withAPIResult($optCall);
});

$app->GET('/api/option/{name}', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $optCall = APICaller::run($ptApp, 'option', 'get', $args['name']);
    return $response->withAPIResult($optCall);
});

$app->POST('/api/option', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $opt = Option::loadFromId($tab['name'], $ptApp->getDao());
    if ($opt == null) {
        $opt = new Option();
    }
    try {
        $opt->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'option', 'write',
            $opt));
});

$app->DELETE('/api/option/{name}', function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $optCall = APICaller::run($ptApp, 'option', 'delete', $args['name']);
    return $response->withAPIResult($optCall);
});

