<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Floor;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

$app->POST('/api/places',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $structFloors = $request->getParsedBody();
    if ($structFloors === null) {
        return $response->withStatus(400, 'Unable to parse input data');
    }
    $floors = [];
    foreach ($structFloors as $jsFloor) {
        $floor = null;
        if (!empty($jsFloor['id'])) {
            $floor = Floor::loadFromId($jsFloor['id'], $ptApp->getDao());
        }
        if ($floor === null) {
            $floor = new Floor();
        }
        try {
            $floor->merge($jsFloor, $ptApp->getDao());
        } catch (InvalidFieldException $e) {
            return $response->reject($e);
        }
        $floors[] = $floor;
    }
    return $response->withApiResult(APICaller::run($ptApp, 'place', 'write',
            [$floors]));
});
