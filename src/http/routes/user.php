<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET userGetAllGet
 * Summary:
 * Notes: Get a array of all User
 * Output-Formats: [application/json]
 */
$app->GET('/api/user/getAll',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'user', 'getAll'));
});


/**
 * GET userIdGet
 * Summary:
 * Notes: Get a User
 * Output-Formats: [application/json]
 */
$app->GET('/api/user/{id}',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'user', 'get',
            $args['id']));
});

/**
 * GET userIdGet
 * Summary:
 * Notes: Get a User
 * Output-Formats: [application/json]
 */
$app->GET('/api/user/getByName/{name}',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'user', 'getByName',
            $args['name']));
});


/**
 * POST userPasswordPut
 * Summary:
 * Notes: update password of an user
 * Output-Formats: [application/json]
 */
$app->POST('/api/user/{id}/password',
        function ($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $oldPassword = $request->getParsedBodyParam('oldPassword', '');
    $newPassword = $request->getParsedBodyParam('newPassword', '');
    $user = User::loadFromId($args['id'], $ptApp->getDao());
    if ($user === null) {
        return $response->withStatus(404, 'User not found');
    }
    return $response->withApiResult(APICaller::run($ptApp, 'user',
            'updatePassword', [$user, $oldPassword, $newPassword]));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/user',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $user = null;
    if (!empty($tab['id'])) {
        $user = User::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($user === null) {
        $user = new User();
    }
    try {
        $user->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'user', 'write',
        $user));
});
