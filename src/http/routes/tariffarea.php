<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET tariffareaGetAllGet
 * Summary:
 * Notes: Get a array of all TariffArea
 * Output-Formats: [application/json]
 */
$app->GET('/api/tariffarea/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'tariffArea', 'getAll'));
});


/**
 * GET tariffareaIdGet
 * Summary:
 * Notes: Get a TariffArea
 * Output-Formats: [application/json]
 */
$app->GET('/api/tariffarea/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'tariffArea', 'get',
            $args['id']));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/tariffarea',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $ta = null;
    if (!empty($tab['id'])) {
        $ta = TariffArea::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($ta == null) {
        $ta = new TariffArea();
    }
    $otherTa = TariffArea::load($tab['reference'], $ptApp->getDao());
    if ($otherTa !== null) {
        if ($ta->getId() != $otherTa->getId()) {
            $loadKey = null;
            if (!empty($tab['id'])) {
                $loadKey = ['id' => $tab['id']];
            } else {
                $loadKey = TariffArea::getLoadKey($tab);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Currency::class, 'reference', $loadKey, $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $ta->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'tariffArea',
            'write', $ta));
});

/**
 *Create a new tariff area from it's reference. Returns an error if an id is
 * given or if an area already exists with the given reference.
 */
$app->PUT('/api/tariffarea/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $tab['reference'] = $args['reference'];
    $ta = TariffArea::load($tab['reference'], $ptApp->getDao());
    if ($ta !== null) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                TariffArea::class, 'reference',
                ['reference' => $tab['reference']], $tab['reference']);
        return $response->reject($e, 'Reference is already taken');
    }
    $ta = new TariffArea();
    try {
        $ta->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'tariffArea',
            'write', $ta));
});

$app->PATCH('/api/tariffarea/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $ta = TariffArea::load($args['reference'], $ptApp->getDao());
    if ($ta === null) {
        $e = new RecordNotFoundException(TariffArea::class,
                ['reference' => $args['reference']]);
        return $response->notFound($e, 'Tariff area not found');
    }
    if ($tab['reference'] != $args['reference']) {
        $otherTa = TariffArea::load($tab['reference']);
        if ($otherTa !== null) {
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    TariffArea::class, 'reference',
                    ['reference' => $args['reference']], $tab['reference']);
            return $response->reject($e);
        }
    }
    try {
        $ta->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'tariffArea',
            'write', $ta));
});

/**
 * Delete an existing tariff area from it's reference. Returns an error if an id
 * is given or if there aren't any area with this reference.
 */
$app->DELETE('/api/tariffarea/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $ta = TariffArea::load($args['reference'], $ptApp->getDao());
    if ($ta === null) {
        $e = new RecordNotFoundException(TariffArea::class,
                ['reference' => $args['reference']]);
        return $response->notFound($e, 'No tariff area found.');
    }
    return $response->withApiResult(APICaller::run($ptApp, 'tariffArea',
            'delete', $ta->getId()));
});
