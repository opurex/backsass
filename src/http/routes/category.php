<?php

use Pasteque\Server\Exception\InvalidFieldException;
use Pasteque\Server\Exception\RecordNotFoundException;
use Pasteque\Server\Model\Category;
use Pasteque\Server\System\API\APICaller;
use Pasteque\Server\System\API\APIResult;

/**
 * GET categoryGetAll
 * Summary:
 * Notes: Get an array of all Categories
 * Output-Formats: [application/json]
 */
$app->GET('/api/category/getAll',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'category',
            'getAll'));
});

/**
 * GET categoryGetChildrens
 * Summary:
 * Notes: Get an array of Categories from a parent Category Id
 * Output-Formats: [application/json]
 *  * @SWG\Get(
 *     path="/api/category/getChildrens",
 *     @SWG\Response(response="200", description="Get an array of Categories from a parent Category Id")
 * )
 */
$app->GET('/api/category/getChildrens',
         function($request, $response, $args) {
    $queryParams = $request->getQueryParams();
    $parentId = $queryParams['parentId'];
    $ptApp = $this->get('settings')['ptApp'];
    $cat = Category::loadFromId($parentId);
    if ($cat === null) {
        $e = new RecordNotFoundException(Category::class, ['id' => $parentId]);
        return $response->notFound($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'category',
            'getChildren',[$parentId]));
});

$app->GET('/api/category/getChildren/{reference}',
        function($request, $response, $args) {
    $cat = Category::load($args['reference']);
    if ($cat === null) {
        $e = new RecordNotFoundException(Category::class, ['id' => $parentId]);
        return $response->notFound($e);
    }
    return $response->withAPIResult(APICaller::run($ptApp, 'category',
            'getChildren', [$cat->getId()]));
});

/**
 * GET categoryId
 * Summary:
 * Notes: Get a Category
 * Output-Formats: [application/json]
 * @SWG\Get(
 *     path="/api/category/{id}",
 *     @SWG\Response(response="200", description="get a category")
 * )
 */
$app->GET('/api/category/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    return $response->withApiResult(APICaller::run($ptApp, 'category', 'get',
            $args));
});

/**
 * @deprecated Use PUT with reference or POST instead.
 * PUT categoryCreateupdate
 * Summary:
 * Notes: create or modify a category
 * Output-Formats: [application/json]
 * @SWG\Put(
 *     path="/api/category",
 *     tags={"category"},
 *     operationId="updateCategory",
 *     summary="Update an existing category",
 *     description="",
 *     consumes={"application/json", "application/xml"},
 *     produces={"application/xml", "application/json"},
 *     @SWG\Parameter(
 *         name="body",
 *         in="body",
 *         description="category object that needs to be added",
 *         required=true,
 *         @SWG\Schema(ref="#/definitions/Category"),
 *     ),
 *     @SWG\Response(
 *         response=400,
 *         description="Invalid ID supplied",
 *     ),
 *     @SWG\Response(
 *         response=404,
 *         description="Category not found",
 *     ),
 *     @SWG\Response(
 *         response=405,
 *         description="Validation exception",
 *     ),
 *     security={{"pasteque_auth":{"write:categories", "read:categories"}}}
 * )
 */
$app->PUT('/api/category',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $cat = Category::load($tab['reference'], $ptApp->getDao());
    if ($cat != null) {
        $response->withStatus(400, 'Reference is already taken');
    }
    $cat = new Category();
    try {
        $cat->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'category', 'write',
            $category));
});

/** Low level call. If an id is set, it's an update. If not, it's a create. */
$app->POST('/api/category',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    $cat = Category::loadFromId($tab['id'], $ptApp->getDao());
    if ($cat == null) {
        $cat = new Category();
    }
    $otherCat = Category::load($tab['reference'], $ptApp->getDao());
    if ($otherCat !== null) {
        if ($cat->getId() != $otherCat->getId()) {
            $loadKey = null;
            if (!empty($tab['id'])) {
                $loadKey = ['id' => $tab['id']];
            } else {
                $loadKey = Category::getLoadKey($tab);
            }
            $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                    Category::class, 'reference', $loadKey, $tab['reference']);
            return $response->reject($e, 'Reference is already taken');
        }
    }
    try {
        $cat->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'category', 'write',
            $cat));
});

/**
 * Create a new category from it's reference. The reference is read from url
 * and ignored from data.
 * Returns an error if an id is given or if a category already exists
 * with the given reference.
 */
$app->PUT('/api/category/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'New record cannot have an Id');
    }
    $tab['reference'] = $args['reference'];
    $cat = Category::load($tab['reference'], $ptApp->getDao());
    if ($cat != null) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                Category::class, 'reference',
                ['reference' => $args['reference']], $args['reference']);
        return $response->reject($e, 'Reference is already taken');
    }
    $cat = new Category();
    try {
        $cat->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'category', 'write',
            $cat));
});

/**
 * Update an existing category from it's reference. Returns an error if an id
 * is given or if there aren't any category with this reference.
 */
$app->PATCH('/api/category/{reference}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $tab = $request->getParsedBody();
    if (!empty($tab['id'])) {
        return $response->withStatus(400, 'Do not send Id, use reference instead.');
    }
    $cat = Category::load($args['reference'], $ptApp->getDao());
    if ($cat == null) {
        $e = new RecordNotFoundException(Category::class,
                ['reference' => $args['reference']]);
        return $response->notFound($e, 'No category found.');
    }
    try {
        $cat->merge($tab, $ptApp->getDao());
    } catch (InvalidFieldException $e) {
        return $response->reject($e);
    }
    return $response->withApiResult(APICaller::run($ptApp, 'category', 'write',
            $cat));
});
