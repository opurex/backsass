<?php
//    Pastèque API
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
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

namespace Pasteque\Server\API;

use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\Model\Image;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeValue;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\Thumbnailer;

/** Images handling API. It also compresses incoming images. */
class ImageAPI extends APIHelper implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\Image';

    public function __construct($thumbnailer, $dao) {
        $this->thumbnailer = $thumbnailer;
        $this->dao = $dao;
    }

    public static function fromApp($app) {
        return new static($app->getThumbnailer(), $app->getDao());
    }

    protected function updateHasImage($modelName, $id, $hasImage) {
        $model = null;
        $class = Image::getModelClass($modelName);
        if ($class === null) {
            throw new InvalidFieldException(InvalidFieldException::CSTR_ENUM,
                    static::MODEL_NAME, 'model',
                    ['model' => $modelName, 'modelId' => $id], $modelName);
        }
        switch ($modelName) {
            case Image::MODEL_PAYMENTMODE_VALUE:
                // composite primary key,
                // Doctrine requires to use the full model instead of just id.
                $realId = Image::getPMVIdFromModelId($id);
                $pmId = $realId['paymentMode'];
                $realId['paymentMode'] = $this->dao->read(PaymentMode::class,
                        $pmId);
                if ($realId['paymentMode'] === null) {
                    throw new InvalidFieldException(
                            InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                            static::MODEL_NAME, 'modelId',
                            ['model' => $modelName, 'modelId' => $id],
                            $id);
                }
                $model = $this->dao->read(PaymentModeValue::class, $realId);
                break;
            default:
                $model = $this->dao->read(Image::getModelClass($modelName), $id);
        }
        if ($model === null && $hasImage == true) {
            // If the model is not found but the image was removed, it's not a problem
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                    static::MODEL_NAME, 'modelId',
                    ['model' => $modelName, 'modelId' => $id],
                    $id);
        }
        if ($model !== null) {
            $model->setHasImage($hasImage);
            $this->dao->write($model);
        }
    }

    /**
     * Get a single entry from it's ID.
     * @throws InvalidFieldException When the model is not set or is invalid.
     */
    public function get($id) {
        $model = null;
        $modelId = null;
        if (is_array($id)) {
            if (array_key_exists('model', $id)) {
                $model = $id['model'];
            }
            if (array_key_exists('modelId', $id)) {
                $modelId = $id['modelId'];
            }
        }
        $checkModel = Image::getModelClass($model);
        if ($checkModel == null) {
            throw new InvalidFieldException(InvalidFieldException::CSTR_ENUM,
                    static::MODEL_NAME, 'model',
                    ['model' => $model, 'modelId' => $modelId], $model);
        }
        return parent::get($id);
    }

    /**
     * Write images and update 'hasImage' in associated records.
     * @param Image|Image[] The images to write.
     * @return Image|Image[] The updated images.
     * @throws InvalidFieldException with constraint ENUM when the model is not
     * a valid one or with constraint ASSOCIATION_NOT_FOUND when the associated
     * record was not found.
     */
    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        foreach ($data as $d) {
            if ($this->thumbnailer !== null) {
                $thumb = $this->thumbnailer->thumbnail($d->getImage());
                if ($thumb !== false) {
                    // TODO: handle image thumbnail failure
                    $d->setMimeType($thumb->getMimeType());
                    $d->setImage($thumb->getImage());
                }
            }
            // Set hasImage to true in associated model.
            $this->updateHasImage($d->getModel(), $d->getModelId(), true);
        }
        return parent::write(($arrayArgs) ? $data : $data[0]);
    }

    public function delete($id) {
        if (!is_array($id) || empty($id[0])) {
            $ids = array($id);
        } else if (is_array($id)) {
            $ids = $id;
        } else {
            throw new \InvalidArgumentException('Unrecognized Image id format');
        }
        foreach ($ids as $d) {
            // Set hasImage to false in associated model.
            if (empty($d['model']) || empty($d['modelId'])) {
                throw new \InvalidArgumentException('Incompatible id, expecting Image id');
            }
            $this->updateHasImage($d['model'], $d['modelId'], false);
        }
        return parent::delete($ids);
    }

    public function getDefault($model) {
        $resDir = __DIR__ . '/../../../res/images/';
        switch ($model) {
            case Image::MODEL_CATEGORY:
                $img = new Image();
                $img->setModel($model);
                $img->setImage(file_get_contents($resDir . 'default_category.png'));
                $img->setMimeType('image/png');
                return $img;
            case Image::MODEL_PRODUCT:
                $img = new Image();
                $img->setModel($model);
                $img->setImage(file_get_contents($resDir . 'default_product.png'));
                $img->setMimeType('image/png');
                return $img;
            case Image::MODEL_USER:
                $img = new Image();
                $img->setModel($model);
                $img->setImage(file_get_contents($resDir . 'default_avatar.png'));
                $img->setMimeType('image/png');
                return $img;
            case Image::MODEL_CUSTOMER:
                $img = new Image();
                $img->setModel($model);
                $img->setImage(file_get_contents($resDir . 'default_avatar.png'));
                $img->setMimeType('image/png');
                return $img;
            case Image::MODEL_PAYMENTMODE:
            case Image::MODEL_PAYMENTMODE_VALUE:
                $img = new Image();
                $img->setModel($model);
                $img->setImage(file_get_contents($resDir . 'default_generic.png'));
                $img->setMimeType('image/png');
                return $img;
            default:
                throw new InvalidFieldException(InvalidFieldException::CSTR_ENUM,
                        static::MODEL_NAME, 'model',
                        ['model' => $model, 'modelId' => 'default'], $model);
        }
    }

}
