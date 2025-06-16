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

use \Pasteque\Server\Exception\UnicityException;
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for models with reference field is used as user-friendly id. */
abstract class APIRefHelper extends APIHelper
{
    /** Create/Update a record. When ID is not set, look for a record
     * with the same reference. If found, replace it by the new one instead
     * of throwing an error.
     * Sets the ID in $data.
     * @return the ID(s), like for APIHelper->write. */
    public function forceWrite($data) {
        $arrayArgs = is_array($data);
        if (!$arrayArgs) {
            $data = array($data);
        }
        for ($i = 0; $i < count($data); $i++) {
            $d = $data[$i];
            if ($d->getId() == null) {
                // TODO: the ref check query in loop can be optimized
                $refData = $this->search(new DAOCondition('reference', '=', $d->getReference()), 1, 0);
                if (count($refData) == 1) {
                    // Make it managed and get Id
                    $struct = $d->toStruct();
                    $refData[0]->merge($struct, $this->dao);
                    $data[$i] = $refData[0];
                }
            }
        }
        return parent::write(($arrayArgs) ? $data : $data[0]);
    }

    public function write($data) {
        try {
            return parent::write($data);
        } catch (\Exception $e) {
            // Look for duplicated references
            if (!is_array($data)) {
                $ref = $data->getReference();
                $refData = $this->search(new DAOCondition('reference', '=',
                        $ref), 1, 0) ;
                if (count($refData) == 1) {
                    throw new UnicityException(static::MODEL_NAME,
                            'reference', $ref);
                }
            } else {
                $refs = [];
                foreach ($data as $d) {
                    $ref = $d->getReference();
                    if (array_search($ref, $refs) !== false) {
                        throw new UnicityException(static::MODEL_NAME,
                                'reference', $ref);
                    } else {
                        $refs[] = $ref;
                    }
                }
                $refData = $this->search(new DAOCondition('reference',
                        'in', $refs));
                $foundRefs = [];
                foreach ($refData as $rd) {
                    $ref = $rd->getReference();
                    if (array_key_exists($ref, $foundRefs)) {
                        throw new UnicityException(static::MODEL_NAME,
                            'reference', $ref);
                    } else {
                        $foundRefs[$ref] = true;
                    }
                }
            } // Otherwise not a duplicated reference error
            throw $e;
        }
    }

    /** Get a single entry from it's reference. */
    public function getByReference($reference) {
        $data = $this->dao->search(static::MODEL_NAME, new DAOCondition('reference', '=', $reference));
        if (count($data) > 0) {
            return $data[0];
        }
        return null;
    }
}
