<?php

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Image;
use \Pasteque\Server\System\API\APICaller;
use \Pasteque\Server\System\API\APIResult;

$app->GET('/api/image/{model}/default',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $imgCall = APICaller::run($ptApp, 'image', 'getDefault', $args['model']);
    if ($imgCall->getStatus() == APIResult::STATUS_CALL_OK) {
        $img = $imgCall->getContent();
        $mime = $img->getMimeType();
        $data = $img->getImage();
        $response = $response->withStatus(200);
        $response = $response->withHeader('Content-type', $mime);
        $body = $response->getBody();
        $body->write($data);
        return $response;
    } else {
        return $response->withAPIResult($imgCall);
    }
});

/**
 * GET imageIdGet
 * Summary:
 * Notes: get image
 * Output-Formats: MIME type of the image
 * @SWG\Get(
 *     path="/api/image/{model}/{id}",
 *     @SWG\Response(response="200", description="get image")
 * )
 */
$app->GET('/api/image/{model}/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $key = ['model' => $args['model'], 'modelId' => $args['id']];
    $imgCall = APICaller::run($ptApp, 'image', 'get', [$key]);
    if ($imgCall->getStatus() == APIResult::STATUS_CALL_OK) {
        $img = $imgCall->getContent();
        if ($img === null) {
            $e = new RecordNotFoundException(Image::class, $key);
            return $response->notFound($e, "Image not found");
        }
        $mime = $img->getMimeType();
        $data = $img->getImage();
        $response = $response->withStatus(200);
        $response = $response->withHeader('Content-type', $mime);
        $body = $response->getBody();
        $body->write(stream_get_contents($data));
        return $response;
    } else {
        return $response->withAPIResult($imgCall);
    }
});

$app->DELETE('/api/image/{model}/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $imgCall = APICaller::run($ptApp, 'image', 'delete',
            [['model' => $args['model'], 'modelId' => $args['id']]]);
    return $response->withAPIResult($imgCall);
});

$app->PUT('/api/image/{model}/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $key = ['model' => $args['model'], 'modelId' => $args['id']];
    $image = Image::load($key, $ptApp->getDao());
    if ($image !== null) {
        $e = new InvalidFieldException(InvalidFieldException::CSTR_UNIQUE,
                Image::class, 'modelId', $key, $key['modelId']);
        return $response->reject($e);
    }
    $data = $request->getParsedBody();
    $image = new Image();
    $image->setModel($args['model']);
    $image->setModelId($args['id']);
    $image->setMimeType('unchecked');
    $image->setImage($request->getBody()->getContents());
    $imgCall = APICaller::run($ptApp, 'image', 'write', $image);
    return $response->withAPIResult($imgCall);
});


$app->PATCH('/api/image/{model}/{id}',
        function($request, $response, $args) {
    $ptApp = $this->get('settings')['ptApp'];
    $existingImgReq = APICaller::run($ptApp, 'image', 'get',
            [['model' => $args['model'], 'modelId' => $args['id']]]);
    if ($existingImgReq->getStatus() != APIResult::STATUS_CALL_OK) {
        return $response->withAPIResult($existingImgReq);
    }
    if ($existingImgReq->getContent() == null) {
        return $response->withStatus(404, 'No image found.');
    }
    $data = $request->getParsedBody();
    $image = $existingImgReq->getContent();
    $image->setMimeType('unchecked');
    $image->setImage($request->getBody()->getContents());
    $imgCall = APICaller::run($ptApp, 'image', 'write', $image);
    return $response->withAPIResult($imgCall);
});
