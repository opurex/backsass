<?php

namespace Pasteque\HTTP;

use Slim\Http\Response;
use Pasteque\Server\System\API\APIResult;

class APIResponse extends Response
{
    public function withAPIResult($result) {
        $status = $result->getStatus();
        switch ($status) {
            case APIResult::STATUS_CALL_OK:
                return $this->withJson($result->getStructContent());
            case APIResult::STATUS_CALL_REJECTED:
                if (is_string($result->getContent())) {
                    return $this->withStatus(400, $result->getContent());
                } else {
                    return $this->reject($result->getContent());
                }
            case APIResult::STATUS_CALL_ERROR:
                return $this->withStatus(500, $result->getContent());
        }
    }

    /**
     * Get a reject response caused by an exception.
     * @param \Pasteque\Server\Exception\PastequeException $e
     * @param string|null $msg Custom response message to match
     * those that were defined before sending details in the body.
     * @return Response A configured response, with code 400 and the exception
     * in the body.
     */
    public function reject($e, $msg = null) {
        $content = $e->toStruct();
        if ($msg !== null) {
            return $this->withStatus(400, $msg)->withJson($content);
        } else {
            return $this->withStatus(400)->withJson($content);
        }
    }

    /**
     * Get a 404 response with the according content.
     * @param \Pasteque\Server\Exception\RecordNotFoundException $e
     * @param string|null $msg Custom response message to match
     * those that were defined before sending details in the body.
     * @return Response A configured response, with code 400 and the exception
     * in the body.
     */
    public function notFound($e, $msg = null) {
        $content = $e->toStruct();
        if ($msg !== null) {
            return $this->withStatus(404, $msg)->withJson($content);
        } else {
            return $this->withStatus(404)->withJson($content);
        }
    }
}
