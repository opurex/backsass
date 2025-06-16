<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET cashregisterGetAllGet
 * Summary:
 * Notes: Get a array of all CashRegister
 * Output-Formats: [application/json]
 */
$app->GET('/api/cashregister/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'cashRegister', 'getAll'));
});


/**
 * GET cashregisterGetbylabelLabelGet
 * Summary:
 * Notes: Get a cash register by this label
 * Output-Formats: [application/json]
 */
$app->GET('/api/cashregister/getByReference/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $apiRes = APICaller::run($ptApp, 'cashRegister', 'getByReference',
            $args['reference']);
    if ($apiRes->getStatus() == APIResult::STATUS_CALL_OK
            && $apiRes->getContent() === null) {
        $e = new RecordNotFoundException(CashRegister::class,
                ['reference' => $args['reference']]);
        return $response->notFound($e, 'Cash register not found');
    }
    return $response->withApiResult($apiRes);
});

$app->GET('/api/cashregister/getByName/{name}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $apiRes = APICaller::run($ptApp, 'cashRegister', 'getByName',
            $args['name']);
    if ($apiRes->getStatus() == APIResult::STATUS_CALL_OK
            && $apiRes->getContent() === null) {
        $e = new RecordNotFoundException(CashRegister::class,
                ['name' => $args['name']]);
        return $response->notFound($e, 'Cash register not found');
    }
    return $response->withApiResult($apiRes);
});

/**
 * GET cashregisterIdGet
 * Summary:
 * Notes: Get a CashRegiter
 * Output-Formats: [application/json]
 */
$app->GET('/api/cashregister/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $apiRes = APICaller::run($ptApp, 'cashRegister', 'get', $args['id']);
    if ($apiRes->getStatus() == APIResult::STATUS_CALL_OK
            && $apiRes->getContent() === null) {
        $e = new RecordNotFoundException(CashRegister::class,
                ['id' => $args['id']]);
        return $response->notFound($e, 'Cash register not found');
    }
    return $response->withApiResult($apiRes);
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/cashregister',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $cr = null;
    if (!empty($tab['id'])) {
        $cr = CashRegister::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($cr == null) {
        $cr = new CashRegister();
    }
    $otherCr = CashRegister::load($tab['reference'], $ptApp->getDao());
    if ($otherCr !== null) {
        if ($cr->getId() != $otherCr->getId()) {
            $loadKey = null;
            if (!empty($tab['id'])) {
                $loadKey = ['id' => $tab['id']];
            } else {
                $loadKey = CashRegister::getLoadKey($tab);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    CashRegister::class, 'reference',
                    $loadKey, $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $cr->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'cashregister',
            'write', $cr));
});

/**
 * Create a new cash register from it's reference. The reference is read from
 * url and ignored from data.
 * Returns an error if an id is given or if a cash register already exists
 * with the given reference.
 */
$app->PUT('/api/cashregister/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $tab['reference'] = $args['reference'];
    $cr = CashRegister::load($tab['reference'], $ptApp->getDao());
    if ($cr != null) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                CashRegister::class, 'reference',
                ['reference' => $tab['reference']], $tab['reference']);
        return $response->reject($e, 'Reference is already taken');
    }
    $cr = new CashRegister();
    try {
        $cr->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'cashregister',
            'write', $cr));
});

$app->PATCH('/api/cashregister/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $cr = CashRegister::load($args['reference'], $ptApp->getDao());
    if ($cr == null) {
        $e = new RecordNotFoundException(CashRegister::class,
                ['reference' => $args['reference']]);
        return $response->notFound($e, 'Cash register not found');
    }
    if ($tab['reference'] != $args['reference']) {
        $otherCr = CashRegister::load($tab['reference'], $ptApp->getDao());
        if ($otherCr != null) {
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    CashRegister::class, 'reference',
                    ['reference' => $args['reference']], $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $cr->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'cashregister',
            'write', $cr));
});
