<?php

use Pasteque\Server\Exception\InvalidFieldException;
use Pasteque\Server\Exception\RecordNotFoundException;
use Pasteque\Server\Model\Currency;
use Pasteque\Server\System\API\APICaller;
use Pasteque\Server\System\API\APIResult;

/**
 * GET currencyGetAll
 * Summary:
 * Notes: Get an array of all Currencies
 * Output-Formats: [application/json]
 */
$app->GET('/api/currency/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'currency',
            'getAll'));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/currency',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $curr = Currency::loadFromId($tab['id'], $ptApp->getDao());
    if ($curr == null) {
        $curr = new Currency();
    }
    $otherCurr = Currency::load($tab['reference'], $ptApp->getDao());
    if ($otherCurr !== null) {
        if ($curr->getId() != $otherCurr->getId()) {
            $loadKey = null;
            if (!empty($tab['id'])) {
                $loadKey = ['id' => $tab['id']];
            } else {
                $loadKey = Currency::getLoadKey($tab);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Currency::class, 'reference', $loadKey, $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $curr->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'currency', 'write',
            $curr));
});

/**
 * Create a new currency from it's reference. The reference is read from url
 * and ignored from data.
 * Returns an error if an id is given or if a currency already exists
 * with the given reference.
 */
$app->PUT('/api/currency/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $tab['reference'] = $args['reference'];
    $curr = Currency::load($args['reference'], $ptApp->getDao());
    if ($curr !== null) {
        $loadKey = ['reference' => $args['reference']];
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                Currency::class, 'reference', $loadKey, $curr->getReference());
        return $response->reject($e, 'Reference is already taken');
    }
    $curr = new Currency();
    try {
        $curr->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'currency', 'write',
            $curr));
});

/**
 * Update an existing currency from it's reference. Returns an error if an id
 * is given or if there aren't any currency with this reference.
 */
$app->PATCH('/api/currency/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'Do not send Id, use reference instead.');
    }
    $loadKey = ['reference' => $args['reference']];
    $curr = Currency::load($loadKey, $ptApp->getDao());
    if ($curr === null) {
        $e = new RecordNotFoundException(Currency::class, $loadKey);
        return $response->notFound($e, 'No currency found.');
    }
    try {
        $curr->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    if ($tab['reference'] !== $args['reference']) {
        $otherCurr = Currency::structLoad($tab, $ptApp->getDao());
        if ($otherCurr !== null) {
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Currency::class, 'reference', $loadKey,
                    $curr->getReference());
            return $response->reject($e, 'Reference is already taken');
        }
    }
    return $response->withApiResult(APICaller::run($ptApp, 'currency', 'write', $curr));
});
