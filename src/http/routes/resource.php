<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\API\APICaller;

/**
 * GET roleNameGet
 * Summary:
 * Notes: Get a Resource by its label
 * Output-Formats: [application/json]
 */
$app->GET('/api/resource/{label}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'resource', 'get', $args['label']));
});

/** Insert or update a resource. */
$app->POST('/api/resource',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $res = Resource::load($tab['label'], $ptApp->getDao());
    if ($res === null) {
        $res = new Resource();
    }
    try {
        $res->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'resource', 'write',
            $res));
});

$app->PATCH('/api/resource/{label}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $res = Resource::load($args['label'], $ptApp->getDao());
    if ($res === null) {
        $e = new RecordNotFoundException(Resource::class,
                ['label' => $args['label']]);
        return $response->notFound($e);
    }
    if ($tab['label'] != $args['label']) {
        $otherRes = Resource::load($tab['label'], $ptApp->getDao());
        if ($otherRes !== null) {
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Resource::class, 'label',
                    ['label' => $args['label']], $tab['label']);
            return $response->reject($e);
        }
    }
    try {
        $res->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'resource', 'write',
            $res));
});

$app->DELETE('/api/resource/{label}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $label = $args['label'];
    if ($label != 'Printer.Ticket.Logo' && $label != 'Printer.Ticket.Header'
            && $label != 'Printer.Ticket.Footer' && $label != 'MobilePrinter.Ticket.Logo'
            && $label != 'MobilePrinter.Ticket.Header' && $label != 'MobilePrinter.Ticket.Footer') {
        return $response->withStatus(400, 'Cannot delete this resource');
    }
    return $response->withApiResult(APICaller::run($ptApp, 'resource', 'delete',
            $label));
});
