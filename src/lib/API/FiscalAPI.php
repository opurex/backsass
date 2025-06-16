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

use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\DAO\DAOCondition;

class FiscalAPI implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\FiscalTicket';

    public function __construct($dao) {
        $this->dao = $dao;
    }

    public static function fromApp($app) {
        return new static($app->getDao());
    }

    /** Get all fiscal tickets, including EOS'.
     * @param $order The order fields (see DAO->search) */
    public function getAll($order = null) {
        return $this->dao->search(FiscalTicket::class,
            null, null, null, $order);
    }

    public function search($conditions, $count = null, $offset = null, $order) {
        return $this->dao->search(static::MODEL_NAME, $conditions,
                $count, $offset, $order);
    }

    public function getLastFiscalTicket($type, $sequence) {
        // Look for an existing fiscal ticket
        $lastFTicket = null;
        $lastSearch = $this->dao->search(FiscalTicket::class,
                [new DAOCondition('type', '=', $type),
                 new DAOCondition('sequence', '=', $sequence)],
                1, null, '-number');
        if (count($lastSearch) > 0) {
            $lastFTicket = $lastSearch[0];
            if ($lastFTicket->getNumber() === 0) {
                // Ignore EOS.
                return null;
            }
            return $lastFTicket;
        }
        // No fiscal ticket found.
        return null;
    }

    public function getSequences() {
        $em = $this->dao->getEntityManager();
        $q = $em->createQuery('select distinct(s.sequence) '
                . 'from \Pasteque\Server\Model\FiscalTicket s ');
        $sequences = $q->getResult();
        $result = [];
        foreach ($sequences as $seq) {
            $result[] = $seq[1];
        }
        return $result;
    }


    public function getTypes() {
        $em = $this->dao->getEntityManager();
        $q = $em->createQuery('select distinct(s.type) '
                . 'from \Pasteque\Server\Model\FiscalTicket s '
                . 'order by s.type');
        $types = $q->getResult();
        $result = [];
        foreach ($types as $type) {
            $result[] = $type[1];
        }
        return $result;
    }

    public function count($sequence, $type) {
        $em = $this->dao->getEntityManager();
        $q = $em->createQuery('select count(s.sequence) '
                . 'from \Pasteque\Server\Model\FiscalTicket s '
                . 'where s.type = :type and s.sequence = :sequence') ;
        $q->setParameters(['type' => $type,
                           'sequence' => $sequence]);
        $sequences = $q->getResult();
        $result = [];
        if (count($sequences) > 0) {
            return $sequences[0][1];
        } else {
            return 0;
        }
 
    }

    /** Count the number of z tickets, including EOS. */
    public function countZ($sequence) {
        return $this->count($sequence, FiscalTicket::TYPE_ZTICKET);
    }

    /** Count the number of tickets, including EOS. */
    public function countTickets($sequence) {
        return $this->count($sequence, FiscalTicket::TYPE_TICKET);
    }

    /** List Z tickets from a $sequence. If $page > 0 it will also fetch
     * the previous Z to be able to check the signature. */
    public function listZ($sequence, $count, $page) {
        return $this->listByType(FiscalTicket::TYPE_ZTICKET, $sequence, $count, $page);
    }

    /** List tickets from a $sequence. If $page > 0 it will also fetch
     * the previous ticket to be able to check the signature. */
    public function listTickets($sequence, $count, $page) {
        return $this->listByType(FiscalTicket::TYPE_TICKET, $sequence, $count, $page);
    }

    /** List custom tickets from a $sequence. If $page > 0 it will also
     * fetch the previous ticket to be able to check the signature. */
    public function listByType($type, $sequence, $count, $page) {
        $em = $this->dao->getEntityManager();
        $q = $em->createQuery('select s '
                . 'from \Pasteque\Server\Model\FiscalTicket s '
                . 'where s.sequence = :seq and s.type = :type '
                . 'order by s.number asc');
        $q->setParameters(['seq' => $sequence,
                        'type' => $type]);
        $offset = 1; // Avoid EOS
        $includePrevious = false;
        if ($page > 0) {
            $offset = $count * $page - 1; // +1 (EOS) - 1 (previous)
            $includePrevious = true;
        }
        $q->setMaxResults(($includePrevious) ? $count + 1 : $count);
        $q->setFirstResult($offset);
        $tkts = $q->getResult();
        $result = [];
        foreach ($tkts as $res) {
            $result[] = $res;
        }
        if (count($result) < $count) {
            // Add EOS on the last page
            $eosSearch = $this->dao->search(static::MODEL_NAME,
                    [new DAOCondition('type', '=', $type),
                     new DAOCondition('sequence', '=', $sequence),
                     new DAOCondition('number', '=', 0)]);
            if (count($eosSearch) > 0) {
                $result[] = $eosSearch[0];
            }
        }
        return $result;
    }
}
