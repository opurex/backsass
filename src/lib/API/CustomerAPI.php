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

/** CRUD API for Customer. */
class CustomerAPI extends APIHelper implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\Customer';
    const DEFAULT_ORDER = 'dispName';

    public function getTopIds($limit = 10) {
        // This is a report, but there are no reports.
        // So it's uglily hardcoded until then.
        $em = $this->dao->getEntityManager();
        $now = new \DateTime();
        $q = $em->createQuery('select c.id, count(c.id) as num '
                . 'from \Pasteque\Server\Model\Ticket t '
                . 'join t.customer c '
                . 'where c.visible = true '
                . 'and (c.expireDate is null or c.expireDate > :now) '
                . 'group by c.id order by num desc');
        $q->setParameter('now', $now);
        $q->setMaxResults($limit);
        $top = $q->getResult();
        $result = [];
        foreach ($top as $cust) {
            $result[] = $cust['id'];
        }
        return $result;
    }

    public function getTop($limit = 10) {
        $ids = $this->getTopIds($limit);
        $customers = [];
        foreach ($ids as $id) {
            $customer = $this->dao->read(static::MODEL_NAME, $id);
            $customers[] = $customer;
        }
        return $customers;
    }

    /** Write one or multiple customers. Balance cannot be updated this way
     * to prevent unexpected changes. It is updated with tickets or with the
     * dedicated function. */
    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        for ($i = 0; $i < count($data); $i++) {
            $d = $data[$i];
            if ($d->getId() === null) {
                // New record, set an empty balance to start with.
                $d->setBalance(0.0);
            }
            $snapshot = $this->dao->readSnapshot(static::MODEL_NAME,
                    $d->getId());
            if ($snapshot !== null) {
                // Found previous record, pick it's balance.
                $d->setBalance($snapshot->getBalance());
            } else {
                // Old record not found, set an empty balance in case...
                $d->setBalance(0.0);
            }
            $data[$i] = $d;
        }
        return parent::write(($arrayArgs) ? $data : $data[0]);
    }

    /** Update the customer's balance for when the balance is updated
     * outside Pastèque.
     * @return True in case of success, false if the customer
     * cannot be found. */
    public function setBalance($id, $balance) {
        $model = $this->dao->read(static::MODEL_NAME, $id);
        if ($model === null) {
            return false;
        }
        $model->setBalance($balance);
        $this->dao->write($model);
        $this->dao->commit();
        return true;
    }

}
