<?php
//    Pastèque API
//
//    Copyright (C) 2012-2015 Scil (http://scil.coop)
//    Cédric Houbart, Philippe Pary
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

use \Pasteque\Server\Exception\InvalidFieldException;

/**
 * Parent class of all main models (non-embedded) to use with Doctrine.
 * Main models have meaning from themselves and can be retreived independently.
 */
abstract class DoctrineMainModel extends DoctrineModel
{
    /**
     * Get the name of the field that is the preferred way to access to
     * the records. This field must be unique.
     * It is necessary because Doctrine doesn't allow to edit the id.
     * And we often use reference like in API url or non-sql representations.
     */
    protected abstract static function getReferenceKey();

    public abstract function getReference();

    public function idEquals($otherModel) {
        $myId = $this->getId();
        $otherId = $otherModel->getId();
        if (!is_array($myId) && !is_array($otherId)) {
            return $myId === $otherId;
        } else if (is_array($myId) && is_array($otherId)) {
            foreach ($myId as $key => $value) {
                if (!isset($otherId[$key]) || $value !== $otherId[$key]) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get an instance from it's internal id.
     * Id will be strictly internal in future version of the API. But it is
     * still used for exported records and some model does not have a
     * reference-like property.
     * @deprecated Use load instead when possible.
     * @return A record linked to the DAO, or a new instance.
     */
    public static function loadFromId($id, $dao) {
        return $dao->read(static::class, $id);
    }

    /**
     * Load an instance from it's preferred reference.
     * @param $ref The value of static::getReferenceKey() to search for.
     * @param $dao The dao that will look for the record.
     * @return A record linked to the DAO, or null.
     */
    public static function load($ref, $dao) {
        $refField = static::getReferenceKey();
        $conditions = [];
        if (!is_array($refField)) {
            $sRef = $ref;
            if (is_array($ref) && array_key_exists($refField, $ref)) {
                $sRef = $ref[$refField];
            }
            $conditions[] = new DAOCondition(static::getReferenceKey(),
                    '=', $sRef);
        } else {
            foreach ($refField as $fieldName) {
                if (!array_key_exists($fieldName, $ref)) {
                    return null;
                }
                $conditions[] = new DAOCondition($fieldName, '=',
                        $ref[$fieldName]);
            }
        }
        $search = $dao->search(static::class, $conditions, 1);
        if (count($search) > 0) {
            return $search[0];
        } else {
            return null;
        }
    }

    /**
     * Extract the loading key to pass to load() from a struct.
     * @param array $struct
     * @return array An associative array to pass to load().
     */
    public static function getLoadKey($struct) {
        $refField = static::getReferenceKey();
        if (!is_array($refField)) {
            if (array_key_exists($refField, $struct)) {
                return [$refField => $struct[$refField]];
            }
            return null;
        } else {
            $key = [];
            foreach ($refField as $fieldName) {
                if (!array_key_exists($fieldName, $struct)) {
                    return null;
                }
                $key[$fieldName] = $struct[$fieldName];
            }
            return $key;
        }
    }

    /**
     * Combines getLoadKey with load. It doesn't merge.
     * @param array $struct
     * @param $dao The dao that will look for the record.
     * @return A record linked to the DAO, or null.
     */
    public static function structLoad($struct, $dao) {
        return static::load(static::getLoadKey($struct), $dao);
    }

    public function merge($struct, $dao) {
        try {
            parent::merge($struct, $dao);
        } catch (InvalidFieldException $e) {
            $e->setId(static::getLoadKey($struct));
            throw $e;
        }
    }

    /**
     * @Override from DoctrineModel->equals
     */
    public function equals($o) {
        if ($o === null) {
            return false;
        }
        if (!($o instanceof static) && (!($this instanceof $o))) {
            // Double check because Doctrine proxies are subclasses
            return false;
        }
        // Check that each direct field ignoring id shares the same values
        foreach (static::getDirectFieldNames() as $field) {
            $fieldName = $field;
            if ($field instanceof \Pasteque\Server\Model\Field\Field) {
                $fieldName = $field->getName();
            }
            if ($fieldName == 'id') {
                continue;
            }
            if (!$this->directFieldEquals($o, $field)) {
                return false;
            }
        }
        // Check that each association fields ignoring internal fields shares
        // the same values
        foreach (static::getAssociationFields() as $field) {
            if (!empty($field['internal'])) {
                continue;
            }
            if (!$this->associationEquals($o, $field)) {
                return false;
            }
        }
        return true;
    }
}
