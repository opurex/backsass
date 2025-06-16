<?php
//    Pastèque API
//
//    Copyright (C) 2012-2015 Scil (http://scil.coop)
//    Cédric Houbart, Philippe Pary
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

namespace Pasteque\Server\Model;

use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeValue;
use \Pasteque\Server\Model\Field\EnumField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Image.
 * Images are loosely coupled to the other models not to send them with
 * other data. If embedded, the images would be always sent, if not
 * embedded, updating images would be a mess with the ids.
 * Instead other models has a flag named "hasImage" and images are
 * identified by the model name and it's id. This flag is managed by
 * ImageAPI and should not be edited in an other way.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="images")
 */
class Image extends DoctrineMainModel
{
    const MODEL_CATEGORY = 'category';
    const MODEL_PRODUCT = 'product';
    const MODEL_USER = 'user';
    const MODEL_CUSTOMER = 'customer';
    const MODEL_PAYMENTMODE = 'paymentmode';
    const MODEL_PAYMENTMODE_VALUE = 'paymentmodevalue';

    public static function getModelClass($model) {
        switch ($model) {
            case Image::MODEL_CATEGORY:
                return \Pasteque\Server\Model\Category::class;
            case Image::MODEL_PRODUCT:
                return \Pasteque\Server\Model\Product::class;
            case Image::MODEL_USER:
                return \Pasteque\Server\Model\User::class;
            case Image::MODEL_CUSTOMER:
                return \Pasteque\Server\Model\Customer::class;
            case Image::MODEL_PAYMENTMODE:
                return \Pasteque\Server\Model\PaymentMode::class;
            case Image::MODEL_PAYMENTMODE_VALUE:
                return \Pasteque\Server\Model\PaymentModeValue::class;
            default:
                return null;
        }
    }

    protected static function getDirectFieldNames() {
        return [
                new EnumField('model',
                        ['values' => [static::MODEL_CATEGORY,
                        static::MODEL_PRODUCT, static::MODEL_USER,
                        static::MODEL_CUSTOMER, static::MODEL_PAYMENTMODE,
                        static::MODEL_PAYMENTMODE_VALUE]]),
                new StringField('modelId'),
                new StringField('mimeType'),
                'image'
                ];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getReferenceKey() {
        return ['model', 'modelId'];
    }
    public function getReference() {
        return $this->getId();
    }

    /** Get the modelId for a PaymentModeValue. */
    public static function getPMVModelId($value) {
        $pm = $value->getPaymentMode();
        if ($pm->getId() == null || $value->getValue() == null) {
            return null;
        }
        return sprintf('%d-%f', $pm->getId(), $value->getValue());
    }

    /** Get the PaymentModeValue id from the modelId. */
    public static function getPMVIdFromModelId($modelId) {
        $split = explode('-', $modelId);
        if (count($split) != 2) {
            return null;
        }
        return ['paymentMode' => intVal($split[0]), 'value' => round(floatVal($split[1]), 5)];
    }

    /** Get the dql wildcard to match all modelId for the given payment mode. */
    public static function getPMVModelIdWildcard($paymentMode) {
        if ($paymentMode->getId() === null) {
            return null;
        }
        return sprintf('%d-*', $paymentMode->getId());
    }

    public function getId() {
        return ['model' => $this->getModel(), 'modelId' => $this->getModelId()];
    }

    /**
     * Type of model the image is for. See constants.
     * @var string
     * @SWG\Property()
     * @Id @Column(type="string")
     */
    protected $model;
    public function getModel() { return $this->model; }
    public function setModel($model) { $this->model = $model; }

    /**
     * ID of the model the image is for.
     * It is a string to allow multi-fied ids as a JSON string.
     * @var string
     * @SWG\Property()
     * @Id @Column(type="string")
     */
    protected $modelId;
    public function getModelId() { return $this->modelId; }
    /** Set the model id. It accepts a single value (int, string),
     * or an array that is convented in json format. */
    public function setModelId($modelId) {
        if (is_array($modelId)) {
            $this->modelId = json_encode($modelId);
        } else {
            $this->modelId = $modelId;
        }
    }

    /**
     * Mime type
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $mimeType;
    public function getMimeType() { return $this->mimeType; }
    public function setMimeType($mimeType) { $this->mimeType = $mimeType; }

    /**
     * The actual image
     * @var binary
     * @SWG\Property()
     * @Column(type="blob")
     */
    protected $image;
    public function getImage() { return $this->image; }
    public function setImage($image) { $this->image = $image; }

    public function toStruct() {
        // Handle base64 encoding of binary image
        $struct = parent::toStruct();
        switch (gettype($struct['image'])) {
            case 'resource':
                $struct['image'] = stream_get_contents($struct['image']);
                break;
            default:
                // already a binary string
        }
        $struct['image'] = base64_encode($struct['image']);
        return $struct;
    }

    public function merge($struct, $dao) {
        // Convert incoming base64 data into binary before reading
        $newStruct = ['model' => $struct['model'],
                'modelId' => $struct['modelId'],
                'mimeType' => $struct['mimeType'],
                'image' => base64_decode($struct['image'])];
        if (isset($struct['id'])) {
            $newStruct['id'] = $struct['id'];
        }
        parent::merge($newStruct, $dao);
    }

}
