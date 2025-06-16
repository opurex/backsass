<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

$app->GET('/api/paymentmode/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'paymentmode',
            'getAll'));
});

$app->GET('/api/paymentmode/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'paymentmode',
            'get', $args));

});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/paymentmode',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $pm = null;
    if (!empty($tab['id'])) {
        $pm = PaymentMode::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($pm == null) {
        $pm = new PaymentMode();
    }
    $otherPm = PaymentMode::load($tab['reference'], $ptApp->getDao());
    if ($otherPm !== null) {
        if ($pm->getId() != $otherPm->getId()) {
            $loadKey = null;
            if (!empty($tab['id'])) {
                $loadKey = ['id' => $tab['id']];
            } else {
                $loadKey = PaymentMode::getLoadKey($tab);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    PaymentMode::class, 'reference',
                    $loadKey, $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $pm->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'paymentmode',
            'write', $pm));
});

/**
 * Create a new paymentMode from it's reference. The reference is read from url
 * and ignored from data.
 * Returns an error if an id is given or if a payment mode already exists
 * with the given reference.
 */
$app->PUT('/api/paymentmode/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $tab['reference'] = $args['reference'];
    $pm = PaymentMode::load($args['reference'], $ptApp->getDao());
    if ($pm !== null) {
        $loadKey = ['reference' => $args['reference']];
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                PaymentMode::class, 'reference',
                $loadKey, $tab['reference']);
        return $response->reject($e, 'Reference is already taken');
    }
    $pm = new PaymentMode();
    try {
        $pm->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'paymentmode',
            'write', $pm));
});

/**
 * Update an existing paymentMode from it's reference. Returns an error if an
 * id is given or if there aren't any payment mode with this reference.
 */
$app->PATCH('/api/paymentmode/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'Do not send Id, use reference instead.');
    }
    $loadKey = ['reference' => $args['reference']];
    $pm = PaymentMode::load($loadKey, $ptApp->getDao());
    if ($pm === null) {
        $e = new RecordNotFoundException(PaymentMode::class, $loadKey);
        return $response->notFound($e, 'No payment mode found.');
    }
    if ($tab['reference'] !== $args['reference']) {
        $otherPm = PaymentMode::structLoad($tab, $ptApp->getDao());
        if ($otherPm !== null) {
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    PaymentMode::class, 'reference', $loadKey,
                    $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $pm->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'paymentmode',
            'write', $pm));
});
