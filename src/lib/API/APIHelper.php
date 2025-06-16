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

use \Pasteque\Server\System\DAO\DAOFactory;

/** Helper for simple data CRUD. */
abstract class APIHelper implements API
{
    protected $dao;
    /** Override this const in children classes
     * to the full-qualified model name. */
    const MODEL_NAME = null;
    /** Override this const in children classes to add a default sorting order.
     * It is a string or an array passed to DAO->search. */
    const DEFAULT_ORDER = null;

    public function __construct($dao) {
        $this->dao = $dao;
    }

    public static function fromApp($app) {
        return new static($app->getDao());
    }

    /** Use default order if no order is given. */
    protected function getOrder($order) {
        if ($order === null && static::DEFAULT_ORDER !== null) {
            $order = static::DEFAULT_ORDER;
        }
        return $order;
    }

    /** Get a single entry from it's ID. */
    public function get($id) {
        return $this->dao->read(static::MODEL_NAME, $id);
    }

    /** Get all entries.
     * @param $order The order fields (see DAO->search) */
    public function getAll($order = null) {
        $order = $this->getOrder($order);
        return $this->dao->search(static::MODEL_NAME,
            null, null, null, $order);
    }

    public function count($conditions = array()) {
        return $this->dao->count(static::MODEL_NAME, $conditions);
    }

    public function search($conditions, $count = null, $offset = null, $order = null) {
        $order = $this->getOrder($order);
        return $this->dao->search(static::MODEL_NAME, $conditions,
                $count, $offset, $order);
    }

    /** Check if record(s) can be handled by this helper.
     * @param $data Single or array of records to check.
     * @throws \InvalidArgumentException when the class of at least
     * one record doesn't match the one handled by this helper.
     * @return True. Exception otherwise. */
    protected function supportOrDie($data) {
        // Mid or feed
        if (!is_array($data)) {
            if (get_class($data) != static::MODEL_NAME) {
                throw new \InvalidArgumentException(sprintf('Incompatible class %s expecting %s', get_class($data), static::MODEL_NAME));
            }
        } else {
            foreach ($data as $d) {
                if (get_class($d) != static::MODEL_NAME) {
                    throw new \InvalidArgumentException(sprintf('Incompatible class %s expecting %s', get_class($d), static::MODEL_NAME));
                }
            }
        }
        return true;
    }

    /** Write a single or an array of entries. If the ID is set, it's an update,
     * otherwise it's a create. Sets the ID in $data.
     * $data Entities to write/update.
     * @return The single entry or the array of entries.
     * @throw \InvalidArgumentException when at least one entry is not of the
     * managed class. */
    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        foreach ($data as $d) {
            $this->dao->write($d);
        }
        $this->dao->commit();
        if ($arrayArgs) {
            return $data;
        } else {
            return $data[0];
        }
    }

    /** Delete a single or an array of entries by their ID.
     * If an ID is not mapped to any record, it is ignored.
     * @return the number of elements deleted. */
    public function delete($id) {
        $id = (is_array($id)) ? $id : array($id);
        $references = array();
        foreach ($id as $i) {
            $model = $this->dao->read(static::MODEL_NAME, $i);
            if ($model != null) {
                $references[] = $model;
            }
        }
        foreach ($references as $ref) {
            $this->dao->delete($ref);
        }
        $this->dao->commit();
        return count($references);
    }
}
