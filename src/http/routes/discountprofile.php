<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\DiscountProfile;
use \Pasteque\Server\System\API\APICaller;

/**
 * GET discountprofileGetAllGet
 * Summary:
 * Notes: Get an array of all DiscountProfiles
 * Output-Formats: [application/json]
 * @SWG\Get(
 *     path="/api/discountprofile/getAll",
 *     @SWG\Response(response="200", description="Get an array of all DiscountProfiles")
 * )
 */
$app->GET('/api/discountprofile/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'discountprofile',
            'getAll'));
});


/**
 * GET discountprofileIdGet
 * Summary:
 * Notes: Get a DiscountProfile
 * Output-Formats: [application/json]
 * @SWG\Get(
 *     path="/api/discountprofile/{id}",
 *     @SWG\Response(response="200", description="Get a DiscountProfile")
 * )
 */
$app->GET('/api/discountprofile/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'discountprofile',
            'get', $args['id']));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/discountprofile',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $profile = null;
    if (!empty($tab['id'])) {
        $profile = DiscountProfile::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($profile === null) {
        $profile = new DiscountProfile();
    }
    try {
        $profile->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'discountprofile',
            'write', $profile));
});
