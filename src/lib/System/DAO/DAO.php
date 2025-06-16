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

namespace Pasteque\Server\System\DAO;

/** Data Access Object interface,
 * which holds all the database read/write operations.
 * An instance is created for reading/writing. All writing
 * operations from the DAO are run in a transaction that requires
 * an explicit call to commit.
 * Get a DAO instance from DAOFactory to ease refactoring. */
interface DAO
{
    /** Explicitely write data. In case of error, automatically rollback.
     * The the ID of new entries are accessible set at that time. */
    public function commit();

    /** Insert/update data.
     * @param $model The model instance to instert in database. */
    public function write($model);

    /** Get the total number of data */
    public function count($modelName, $conditions = array());

    /** Read a single object from it's id.
     * If id is null or nothing is found, return null. */
    public function read($modelName, $id);

    /** Get the content of the database, only for read purposes.
     * This forces to skip any existing cache and link from the DAO.
     * The result cannot be used with write or anything else. */
    public function readSnapshot($modelName, $id);

    /** Search objects with given condition and optional pagination
     * @param $conditions an array of DAOConditions. If null (default),
     * look for the entire list.
     * @param $count: optional max result count
     * @param $offset: optional start offset. If offset is used,
     * $count must be set.
     * @param $order Array or string field name(s) for ordering. The field name
     * is prefixed by '-' for ordering DESC, '+' (default) for ASC.
     * @return an array of objects
     * @throws \InvalidArgumentException When $conditions is not well formed.
     */
    public function search($class, $conditions = null,
            $count = null, $offset = null, $order = array());

    /** Delete a registered model.
     * If the model is not registerd, does nothing. */
    public function delete($model);

    /** Cleanly close the DAO. An other one has to be created after that. */
    public function close();
}