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

namespace Pasteque\Server\CommonAPI;

use \Pasteque\Server\API\API;
use \Pasteque\Server\Exception\InvalidRecordException;
use \Pasteque\Server\System\DAO\DAOCondition;

class OptionAPI implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\Option';

    public function __construct($dao) {
        $this->dao = $dao;
    }

    public static function fromApp($app) {
        return new OptionAPI($app->getDao());
    }

    /** Use default order if no order is given. */
    protected function getOrder($order) {
        if ($order === null) {
            return 'name';
        }
        return $order;
    }

    /** Get all non-system options. */
    public function getAll($order = null) {
        $order = $this->getOrder($order);
        return $this->dao->search(static::MODEL_NAME,
            new DAOCondition('system', '=', false), null, null, $order);
    }
 
    /** Get a single entry from it's name. It can be a system option or not. */
    public function get($name) {
        return $this->dao->read(static::MODEL_NAME, $name);
    }

    /** Check if record(s) can be handled by this helper.
     * @param $data Single or array of records to check.
     * @throws \InvalidArgumentException when the class of at least
     * one record doesn't match the one handled by this helper.
     * @return True. Exception otherwise. */
    protected function supportOrDie($data) {
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
     * System options cannot be written, use writeSystem instead.
     * $data Entities to write/update.
     * @return The single entry or the array of entries.
     * @throw InvalidArgumentException when at least one entry is not of the
     * managed class.
     * @throw InvalidRecordException when at least one entry
     * is a system option. */
    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        foreach ($data as $d) {
            if ($d->isSystem()) {
                throw new InvalidRecordException(InvalidRecordException::CSTR_READ_ONLY,
                        static::MODEL_NAME, $d->getName());
            }
            $this->dao->write($d);
        }
        $this->dao->commit();
        if ($arrayArgs) {
            return $data;
        } else {
            return $data[0];
        }
    }

    /** Write a single or an array of entries. If the ID is set, it's an update,
     * otherwise it's a create. Sets the ID in $data.
     * Can write either system on non-system options.
     * $data Entities to write/update.
     * @return The single entry or the array of entries.
     * @throw InvalidArgumentException when at least one entry is not of the
     * managed class. */
    public function writeSystem($data) {
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
     * System options cannot be deleted.
     * @return the number of elements deleted.
     * @throw InvalidRecordException when at least one option
     * is a system one. */
    public function delete($id) {
        $id = (is_array($id)) ? $id : array($id);
        $references = array();
        foreach ($id as $i) {
            $model = $this->dao->read(static::MODEL_NAME, $i);
            if ($model != null) {
                $references[] = $model;
                if ($model->isSystem()) {
                    throw new InvalidRecordException(InvalidRecordException::CSTR_READ_ONLY,
                        static::MODEL_NAME, $model->getName());
                }
            }
        }
        foreach ($references as $ref) {
            $this->dao->delete($ref);
        }
        $this->dao->commit();
        return count($references);
    }
}
