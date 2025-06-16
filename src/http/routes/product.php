<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

/**
 * GET productGetAllGet
 * Summary:
 * Notes: Get a array of all Product
 * Output-Formats: [application/json]
 */
$app->GET('/api/product/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'product',
            'getAll'));
});


/**
 * GET productGetByCategoryCategoryGet
 * Summary:
 * Notes: getCategory description
 * Output-Formats: [application/json]
 */
$app->GET('/api/product/getByCategory/{category}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'product',
            'getFromCategory', $args['category']));
});


/**
 * GET productGetbycodeCodeGet
 * Summary:
 * Notes: Get a Product by code
 * Output-Formats: [application/json]
 */
$app->GET('/api/product/getByCode/{code}',
            function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'product',
            'getByCode', $args['code']));
});


/**
 * GET productGetbyreferenceReferenceGet
 * Summary:
 * Notes: Get a Product by reference
 * Output-Formats: [application/json]
 */
$app->GET('/api/product/getByReference/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'product',
            'getByReference', $args['reference']));
});


/**
 * GET productIdGet
 * Summary:
 * Notes: Get a Product
 * Output-Formats: [application/json]
 */
$app->GET('/api/product/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'product', 'get',
            $args['id']));
});


/**
 * @deprecated Use PUT with a reference or POST instead.
 * PUT productCreateupdate
 * Summary:
 * Notes: create or modify a product
 * Output-Formats: [application/json]
 * @SWG\Put(
 *     path="/api/product",
 *     tags={"product"},
 *     operationId="updateProduct",
 *     summary="Update an existing product",
 *     description="",
 *     consumes={"application/json", "application/xml"},
 *     produces={"application/xml", "application/json"},
 *     @SWG\Parameter(
 *         name="body",
 *         in="body",
 *         description="Product object that needs to be added",
 *         required=true,
 *         @SWG\Schema(ref="#/definitions/Product"),
 *     ),
 *     @SWG\Response(
 *         response=400,
 *         description="Invalid ID supplied",
 *     ),
 *     @SWG\Response(
 *         response=404,
 *         description="Product not found",
 *     ),
 *     @SWG\Response(
 *         response=405,
 *         description="Validation exception",
 *     ),
 *     security={{"pasteque_auth":{"write:products", "read:products"}}}
 * )
 */
$app->PUT('/api/product',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $prd = new Product();
    $otherPrd = Product::load($tab['reference'], $ptApp->getDao());
    if ($otherPrd !== null) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                Product::class, 'reference',
                ['reference' => $tab['reference']], $tab['reference']);
        return $response->reject($e, 'Reference is already taken');
    }
    try {
        $prd->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'product', 'write',
            $prd));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/product',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $prd = null;
    if (!empty($tab['id'])) {
        $prd = Product::loadFromId($tab['id'], $ptApp->getDao());
    }
    if ($prd == null) {
        $prd = new Product();
    }
    $otherPrd = Product::load($tab['reference'], $ptApp->getDao());
    if ($otherPrd !== null) {
        if ($prd->getId() != $otherPrd->getId()) {
            $loadKey = null;
            if (!empty($tab['id'])) {
                $loadKey = ['id' => $tab['id']];
            } else {
                $loadKey = Product::getLoadKey($tab);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Product::class, 'reference', $loadKey, $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $prd->merge($tab,$ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'product', 'write',
            $prd));
});

/**
 * Create a new product from it's reference. Returns an error if an id is given
 * or if a product already exists with the given reference.
 */
$app->PUT('/api/product/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $tab['reference'] = $args['reference'];
    $prd = Product::load($tab['reference'], $ptApp->getDao());
    if ($prd !== null) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                Product::class, 'reference',
                ['reference' => $tab['reference']], $tab['reference']);
        return $response->reject($e, 'Reference is already taken');
    }
    $prd = new Product();
    try {
        $prd->merge($tab,$ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'product', 'write',
            $prd));
});

/**
 * Update an existing product from it's reference. Returns an error if an id
 * is given or if there aren't any product with this reference.
 */
$app->PATCH('/api/product/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'Do not send Id, use reference instead.');
    }
    $prd = Product::load($args['reference'], $ptApp->getDao());
    if ($prd === null) {
        $e = new RecordNotFoundException(Product::class, $args['reference']);
        return $response->notFound($e, 'No product found.');
    }
    if ($tab['reference'] != $args['reference']) {
        $otherPrd = Product::load($tab['reference'], $ptApp->getDao());
        if ($otherPrd !== null) {
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Product::class, 'reference',
                    ['reference' => $args['reference']], $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $prd->merge($tab,$ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'product', 'write',
        $prd));
});
