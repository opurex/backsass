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
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\Field\Field;

/**
 * Parent class of embedded models to use with Doctrine.
 * Embedded models cannot be retreived outside their parent.
 */
abstract class DoctrineEmbeddedModel extends DoctrineModel
{
    /**
     * Get the name of the field that is assigned to the parent when
     * loading a record.
     */
    protected abstract static function getParentFieldName();

    /*
     * Load an embedded record that can be safely written to
     * the database afterward.
     * @param $struct The associative array representing this embedded record.
     * It is not automatically merged.
     * @param $parentRecord The parent record that holds this record. It is
     * used guess the id of the embedded record if it is not provided
     * explicitely (which most of the time make no sense to).
     * @param $dao The DAO that will look for an existing record to overwrite.
     * @return The record linked to the database that can be attached to the
     * parent. Or null when not found.
     */
    public static function load($struct, $parentRecord, $dao) {
        $record = null;
        if (isset($struct['id'])) {
            return $dao->read(static::class, $struct['id']);
        } else {
            /* Maybe the id was not set, because that doesn't make sense
             * to do it for an embedded record. */
            if ($parentRecord->getId() != null) {
                /* Try to rebuild the id automatically and look for the record
                 * when updating an existing parent record.
                 * This is necessary because Doctrine will fail to write it
                 * because of the unbound already existing record.
                 * Unity constraint violation exception is raised in that case.
                 */
                $autoId = [];
                $idFields = $dao->getEntityManager()->getClassMetadata(static::class)->getIdentifier();
                if (count($idFields) == 1 && $idFields[0] == 'id') {
                    // New stand-alone embedded record
                    return null;
                } else {
                    foreach ($idFields as $idField) {
                        $matched = false;
                        // Look for a direct field, that is surely set.
                        foreach (static::getDirectFieldNames() as $field) {
                            $fieldName = $field;
                            if ($field instanceof Field) {
                                $fieldName = $field->getName();
                            }
                            if ($idField == $fieldName) {
                                $autoId[$idField] = $struct[$idField];
                                $matched = true;
                                break;
                            }
                        }
                        if ($matched) {
                            continue;
                        }
                        // Look for the parent field
                        if ($idField == static::getParentFieldName()) {
                            $autoId[$idField] = $parentRecord->getId();
                            continue;
                        }
                        // Look for an other association field.
                        foreach (static::getAssociationFields() as $field) {
                            if ($idField == $field['name']) {
                                if (array_key_exists($field['name'],
                                            $struct)) {
                                    $autoId[$field['name']] = $struct[$field['name']];
                                }
                            }
                        }
                    }
                }
                // Now that the Id is recovered, try to read it.
                if ($record === null) {
                    return $dao->read(static::class, $autoId);
                }
            } // End guess id from parent.
        }
        return null;
    }

    /**
     * Get a record that can safely be handled by Doctrine.
     * It calls load() and create a new record if nothing was found.
     * @return The record either read from the database or a new instance.
     */
    public static function loadOrCreate($struct, $parentRecord, $dao) {
        $record = static::load($struct, $parentRecord, $dao);
        if ($record === null) {
            $record = new static();
        }
        return $record;
    }

    /**
     * Same as parent::toStruct but add the parent's reference field for
     * compatibility. This parent field is deprecated and will be removed
     * in future versions.
     */
    public function toStruct() {
        $struct = parent::toStruct();
        $parentField = static::getParentFieldName();
        $getterName = $this->findMethodNameOrThrow('get', $parentField);
        $getter = new \ReflectionMethod(static::class, $getterName);
        $parent = $getter->invoke($this, null);
        $struct[$parentField] = $parent->getId();
        return $struct;
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
        // Check that each association fields ignoring internal fields
        // and parent shares the same values
        foreach (static::getAssociationFields() as $field) {
            if (!empty($field['internal'])
                    || $field['name'] == static::getParentFieldName()) {
                continue;
            }
            if (!$this->associationEquals($o, $field)) {
                return false;
            }
        }
        return true;
    }
}
