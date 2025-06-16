<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\System\API\APICaller;

/**
 * GET roleGetAllGet
 * Summary:
 * Notes: Get a array of all Role
 * Output-Formats: [application/json]
 */
$app->GET('/api/role/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'role', 'getAll'));
});


/**
 * GET roleIdGet
 * Summary:
 * Notes: Get a Role
 * Output-Formats: [application/json]
 */
$app->GET('/api/role/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'role', 'get', $args['id']));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/role',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $role = null;
    if (!empty($tab['id'])) {
        $role = Role::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($role === null) {
        $role = new Role();
    }
    try {
        $role->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'role', 'write',
            $role));
});
