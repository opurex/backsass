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

use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\TariffAreaPrice;
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for TariffArea (attention: lowercase a in name). */
class TariffareaAPI extends APIRefHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\TariffArea';
    const DEFAULT_ORDER = 'dispOrder';

    public function write($data) {
        /* This is the same as APIHelper::write but using dql to remove
         * orphaned prices because it doesn't work with Mysql because of the
         * composite primary key. */
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        foreach ($data as $d) {
            $update = $d->getId() !== null;
            $this->dao->write($d);
            if (!$update) {
                continue;
            }
            // Remove orphan prices
            // Get ids of products that are still in prices
            // and delete those that are not in it from database.
            $keptPrd = [];
            foreach ($d->getPrices() as $price) {
                $prdId = $price->getProduct()->getId();
                if ($prdId !== null) {
                    // just in case
                    $keptPrd[] = $price->getProduct();
                }
            }
            $em = $this->dao->getEntityManager();
            $dql = 'delete \Pasteque\Server\Model\TariffAreaPrice p '
                . 'where p.tariffArea = ?1';
            $q = null;
            if (count($keptPrd) > 0) {
                $dql .= ' and (p.product not in (';
                $params = [];
                for ($i = 0; $i < count($keptPrd); $i++) {
                    $params[] = '?' . ($i + 2);
                }
                $dql .= implode(', ', $params);
                $dql .= '))';
                $q = $em->createQuery($dql);
                for ($i = 0; $i < count($keptPrd); $i++) {
                    $q->setParameter($i + 2, $keptPrd[$i]);
                }
            } else {
                $q = $em->createQuery($dql);
            }
            $q->setParameter(1, $d);
            $q->execute();
        }
        $this->dao->commit();
        if ($arrayArgs) {
            return $data;
        } else {
            return $data[0];
        }
    }

    public function delete($id) {
        /* This is the same as APIHelper delete but explicitely delete
         * associated prices because cascading doesn't work well
         * with Mysql for an obscure reason. */
        $id = (is_array($id)) ? $id : array($id);
        $references = array();
        foreach ($id as $i) {
            $model = $this->dao->read(static::MODEL_NAME, $i);
            if ($model != null) {
                $references[] = $model;
            }
        }
        $em = $this->dao->getEntityManager();
        $dql = 'delete \Pasteque\Server\Model\TariffAreaPrice p '
                . 'where p.tariffArea = ?1';
        $q = $em->createQuery($dql);
        $this->dao->startTransaction();
        foreach ($references as $ref) {
            $q->setParameter(1, $ref);
            $q->execute();
            $this->dao->delete($ref);
        }
        $this->dao->commit();
        return count($references);
    }
}
