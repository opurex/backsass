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

use \Pasteque\Server\Model\Image;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\PaymentModeReturn;
use \Pasteque\Server\Model\PaymentModeValue;
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for PaymentMode (attention: lowercase m in name). */
class PaymentmodeAPI extends APIRefHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\PaymentMode';
    const DEFAULT_ORDER = 'dispOrder';

    // Same as APIHelper::write but delete return and values for replacement.
    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        // Keep track of PaymentModeValue images to restore them after
        $imageIds = [];
        foreach ($data as $d) {
            if ($d->getId() == null) {
                // New payment mode, it cannot have an image.
                continue;
            }
            $snapPm = $this->dao->readSnapshot(PaymentMode::class, $d->getId());
            foreach ($snapPm->getValues() as $pmv) {
                if ($pmv->hasImage()) {
                    $imageIds[] = Image::getPMVModelId($pmv);
                }
            }
        }
        /* Delete unlinked value images:
         * remove from $imageIds all those which still exists,
         * all the remaining are orphaned images. */
        $imageIdsToKeep = [];
        foreach($imageIds as $imgId) {
            $arrayId = Image::getPMVIdFromModelId($imgId);
            foreach ($data as $d) {
                if ($d->getId() != $arrayId['paymentMode']) {
                    continue;
                }
                foreach ($d->getValues() as $pmv) {
                    if (Image::getPMVModelId($pmv) == $imgId) {
                        if (!$pmv->hasImage()) {
                            $pmv->setHasImage(true);
                        }
                        $imageIdsToKeep[] = $imgId;
                        break;
                    }
                }
            }
        }
        $deleteImgIds = [];
        foreach (array_diff($imageIds, $imageIdsToKeep) as $imgId) {
            $deleteImgIds[] = ['model' => Image::MODEL_PAYMENTMODE_VALUE,
                    'modelId' => $imgId];
        }
        if (count($deleteImgIds) > 0) {
            $imgApi = new ImageAPI(null, $this->dao);
            $imgApi->delete($deleteImgIds);
        }
        /* Delete orphaned values and returns, because it doesn't work well
         * with Mysql. */
        foreach ($data as $d) {
            $update = $d->getId() !== null;
            $this->dao->write($d);
            if (!$update) {
                continue;
            }
            // Remove orphan prices
            // Get values and returns of products that are still in prices
            // and delete those that are not in it from database.
            $keptValues = [];
            $keptReturns = [];
            foreach ($d->getValues() as $value) {
                $keptValues[] = $value;
            }
            foreach ($d->getReturns() as $return) {
                $keptReturns[] = $return;
            }
            $em = $this->dao->getEntityManager();
            $dqlVal = 'delete \Pasteque\Server\Model\PaymentModeValue p '
                . 'where p.paymentMode = ?1';
            $dqlValImg = 'delete \Pasteque\Server\Model\Image i '
                . 'where i.model = ?1 and i.modelId like ?2';
            $qVal = null;
            $qValImg = null;
            if (count($keptValues) > 0) {
                $dqlVal .= ' and (p.value not in (';
                $dqlValImg .= ' and (i.modelId not in (';
                $params = [];
                $paramsImg = [];
                for ($i = 0; $i < count($keptValues); $i++) {
                    $params[] = '?' . ($i + 2);
                    $paramsImg[] = '?' . ($i + 3);
                }
                $dqlVal .= implode(', ', $params);
                $dqlVal .= '))';
                $dqlValImg .= implode(', ', $paramsImg);
                $dqlValImg .= '))';
                $qVal = $em->createQuery($dqlVal);
                $qValImg = $em->createQuery($dqlValImg);
                for ($i = 0; $i < count($keptValues); $i++) {
                    $qVal->setParameter($i + 2, $keptValues[$i]->getValue());
                    $qValImg->setParameter($i + 3, Image::getPMVModelId($keptValues[$i]));
                }
            } else {
                $qVal = $em->createQuery($dqlVal);
                $qValImg = $em->createQuery($dqlValImg);
            }
            $qVal->setParameter(1, $d);
            $qVal->execute();
            $qValImg->setParameter(1, Image::MODEL_PAYMENTMODE_VALUE);
            $qValImg->setParameter(2, Image::getPMVModelIdWildcard($d));
            $dqlRet = 'delete \Pasteque\Server\Model\PaymentModeReturn p '
                . 'where p.paymentMode = ?1';
            $qRet = null;
            if (count($keptReturns) > 0) {
                $dqlRet .= ' and (p.minAmount not in (';
                $params = [];
                for ($i = 0; $i < count($keptReturns); $i++) {
                    $params[] = '?' . ($i + 2);
                }
                $dqlRet .= implode(', ', $params);
                $dqlRet .= '))';
                $qRet = $em->createQuery($dqlRet);
                for ($i = 0; $i < count($keptReturns); $i++) {
                    $qRet->setParameter($i + 2, $keptReturns[$i]->getMinAmount());
                }
            } else {
                $qRet = $em->createQuery($dqlRet);
            }
            $qRet->setParameter(1, $d);
            $qRet->execute();
        }
        // Write and commit PaymentModes
        return parent::write(($arrayArgs) ? $data : $data[0]);
    }

    public function delete($id) {
        /* This is the same as APIHelper delete but explicitely delete
         * associated values and returns because cascading doesn't work well
         * with Mysql for an obscure reason. */
        $id = (is_array($id)) ? $id : array($id);
        $references = array();
        $deleteImgIds = [];
        foreach ($id as $i) {
            $model = $this->dao->read(static::MODEL_NAME, $i);
            if ($model === null) {
                continue;
            }
            $references[] = $model;
            foreach ($model->getValues() as $val) {
                if ($val->hasImage()) {
                    $deleteImgIds[] = ['model' => 'paymentmodevalue',
                            'modelId' => json_encode(['paymentMode' => $model->getId(),
                            'value' => $val->getValue()])];
                }
            }
        }
        if (count($deleteImgIds) > 0) {
            $imgApi = new ImageAPI(null, $this->dao);
            $imgApi->delete($deleteImgIds);
        }
        $em = $this->dao->getEntityManager();
        $dqlRet = 'delete \Pasteque\Server\Model\PaymentModeReturn p '
                . 'where p.paymentMode = ?1';
        $dqlVal = 'delete \Pasteque\Server\Model\PaymentModeValue p '
                . 'where p.paymentMode = ?1';
        $qRet = $em->createQuery($dqlRet);
        $qVal = $em->createQuery($dqlVal);
        if (count($deleteImgIds) == 0) {
            $this->dao->startTransaction();
        }
        foreach ($references as $ref) {
            $qRet->setParameter(1, $ref);
            $qVal->setParameter(1, $ref);
            $qRet->execute();
            $qVal->execute();
            $this->dao->delete($ref);
        }
        $this->dao->commit();
        return count($references);
    }
}
