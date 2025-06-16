<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\System\API;

/** Representation of the result of a API call */
class APIResult {

    /** Call went well until the end, content is the result of the code. */
    const STATUS_CALL_OK = 'ok';
    /** Call is invalid (client-side error), content is generally an Exception. */
    const STATUS_CALL_REJECTED = 'rej';
    /** Call encountered a server-side error, content is generally an Exception. */
    const STATUS_CALL_ERROR = 'err';

    /** See class constants. */
    private $status;
    private $content;

    private function __construct($status, $content) {
        $this->status = $status;
        $this->content = $content;
    }

    /**
     * Create a sucessful result.
     * @param mixed $result The result of the method call.
     * @return APIResult The result with status STATUS_CALL_OK and $result
     * in the content.
     */
    public static function success($result) {
        return new APIResult(APIResult::STATUS_CALL_OK, $result);
    }
    /**
     * @deprecated
     * @see APIResult::reject()
     */
    public static function badRequest() {
        return new APIResult(APIResult::STATUS_CALL_REJECTED, 'Bad request');
    }
    /**
     * @deprecated
     * @see APIResult::reject()
     */
    public static function forbidden() {
        return new APIResult(APIResult::STATUS_CALL_REJECTED, 'Forbidden');
    }

    /**
     * Create a reject result.
     * @param \Pasteque\Server\Exception\PastequeException $reason The reason
     * of rejection.
     * @return APIResult The result with status STATUS_CALL_REJECTED and
     * $result in the content.
     */
    public static function reject($reason) {
        return new APIResult(APIResult::STATUS_CALL_REJECTED, $reason);
    }

    /**
     * Create an error result.
     * @param \Exception $err_code The reason of the error.
     * @return APIResult The result with status STATUS_CALL_ERROR and
     * $err_code in the content.
     */
    public static function error($err_code) {
        return new APIResult(APIResult::STATUS_CALL_ERROR, $err_code);
    }

    public function getStatus() { return $this->status; }
    public function getContent() { return $this->content; }

    /** Pass DoctrineModels to toStruct to be able to send the data in JSON.
     * Can be safely used on primitive types and Strings. */
    private function convertDoctrineModel($something) {
        if (($something instanceof \Pasteque\Server\System\DAO\DoctrineModel)
                || ($something instanceof \Pasteque\Server\Model\GenericModel)) {
            return $something->toStruct();
        }
        return $something;
    }

    /** Call getContent() but with struct data instead of objects. */
    public function getStructContent() {
        if (is_array($this->getContent())) {
            $content = array();
            foreach ($this->getContent() as $c) {
                $content[] = $this->convertDoctrineModel($c);
            }
            return $content;
        } else {
            return $this->convertDoctrineModel($this->getContent());
        }
    }

    /** Encode the response to a JSON string. */
    public function toJson() {
        return json_encode(['status' => $this->getStatus(),
            'content' => $this->getStructContent()]);
    }
}
