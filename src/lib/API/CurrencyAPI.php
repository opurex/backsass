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
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for Currency */
class CurrencyAPI extends APIRefHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\Currency';

    public function getMain() {
        $main = $this->search(new DAOCondition('main', '=', true));
        if (count($main) > 0) {
            return $main[0];
        }
        return null;
    }

    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        $main = $this->getMain();
        $updateMain = false;
        $removeMain = false;
        $mainInData = false;
        foreach ($data as $d) {
            if ($main !== null
                    && $d->getReference() == $main->getReference() &&
                    !$d->isMain()) {
                $removeMain = true;
            }
            if ($d->isMain()) {
                $mainInData = true;
                $updateMain = ($main !== null
                        && $d->getReference() != $main->getReference());
            }
        }
        if ($removeMain && !$mainInData) {
            throw new InvalidFieldException(InvalidFieldException::CSTR_DEFAULT_REQUIRED,
                   static::MODEL_NAME, 'main',
                   ['main' => true], false);
        }
        if ($updateMain) {
            $main->setMain(false);
            $this->dao->write($main);
        }
        return parent::write(($arrayArgs) ? $data : $data[0]);
    }

    /** Delete a single or an array of entries by their ID.
     * If an ID is not mapped to any record, it is ignored.
     * @return the number of elements deleted.
     * @throw \BadMethodCallException When trying to delete the main currency */
    public function delete($id) {
        // Just prevent deleting main currency.
        $id = (is_array($id)) ? $id : array($id);
        $references = array();
        foreach ($id as $i) {
            $model = $this->dao->read(static::MODEL_NAME, $i);
            if ($model != null) {
                if ($model->isMain()) {
                    throw new InvalidFieldException(InvalidFieldException::CSTR_DEFAULT_REQUIRED,
                            static::MODEL_NAME, 'main', ['main' => true],
                            false);
                }
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
