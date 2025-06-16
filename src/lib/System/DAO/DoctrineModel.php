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
use \Pasteque\Server\Model\Fields\Field;
use \Pasteque\Server\System\DateUtils;

/** Parent class of all models to use with Doctrine. */
abstract class DoctrineModel
{
    /**
     * Get the list of primitive typed field names or Fields of the model
     * in an array, excluding id.
     * @return <string|Field>[]
     */
    protected abstract static function getDirectFieldNames();

    /**
     * List reference field of the model in an array.
     * A field is an associative
     * array with the following keys:
     * name: The field name (declared in code)
     * class: The full class name of the reference.
     * array (optional): can only be true if set, flag for XtoMany fields.
     * null (optional): can only be true if set, flag for nullable.
     * embedded (optional): can only be true if set, flag for subclasses.
     * Embedded values can be created on the fly from struct and are embedded
     * in toStruct. Non embedded fields are referenced only by id.
     * Embedded classes don't have their own id. They must be a subclass
     * of DoctrineEmbeddedModel.
     * Non embedded models must be a subclass of DoctrineMainModel.
     * internal (optional): can only be true if set. Internal fields are not
     * read and exported in structs.
     */
    protected abstract static function getAssociationFields();

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
     * Extract the value of a field in a suitable format.
     * 'resource' typed values are converted to base64.
     * @param string $fieldName The name of the field (property) to extract.
     * @return The value of that field.
     */
    protected function directFieldToStruct($fieldName) {
        $value = call_user_func(array($this, 'get' . ucfirst($fieldName)));
        switch (gettype($value)) {
            case 'resource':
                return base64_encode(stream_get_contents($value));
            default:
                return $value;
        }
    }

    /**
     * Extract the value of an association field in a suitable format.
     * Non embedded values are converted to their id, embedded values
     * are converted to struct.
     * @param string $field The definition of the field (property) to extract,
     * as retreived from getAssociationFields.
     * @return mixed The id (primitive type, associative array of primitive
     * types) for non-embedded models, the complete associative array of
     * primitive types for embedded models.
     */
    protected function associationFieldToStruct($field) {
        $value = call_user_func([$this, 'get' . ucfirst($field['name'])]);
        // Association field
        if (!empty($field['array'])) {
            // Value is a ArrayCollection
            $struct = array();
            foreach ($value as $v) {
                if (empty($field['embedded'])) {
                    $struct[] = $v->getId();
                } else {
                    $struct[] = $v->toStruct();
                }
            }
            return $struct;
        } else {
            if ($value === null) {
                return null;
            } else {
                if (empty($field['embedded'])) {
                    return $value->getId();
                } else {
                    return $value->toStruct();
                }
            }
        }
    }

    /**
     * Unlink the model from DAO and all methods.
     * All references are converted to their Id.
     * WARNING: To avoid messing up with the entity cache of Doctrine, do never
     * reload records from the exported data.
     * @return An associative array with raw data, suitable for
     * json encoding.
     */
    public function toStruct() {
        // Get Doctrine fields and render them to delete the proxies
        $data = ['id' => $this->getId()];
        foreach (static::getDirectFieldNames() as $field) {
            if ($field instanceof \Pasteque\Server\Model\Field\Field) {
                $fieldName = $field->getName();
                $data[$fieldName] = $this->directFieldToStruct($fieldName);
            } else {
                $data[$field] = $this->directFieldToStruct($field);
            }
        }
        foreach (static::getAssociationFields() as $field) {
            if (empty($field['internal'])) {
                $data[$field['name']] = $this->associationFieldToStruct($field);
            }
        }
        return $data;
    }

    /**
     * Find the name of the method to operate the given field.
     * @param $operation The name of the operation to execute ('set', 'add' etc)
     * @param $fieldName The name of the field.
     * @return The name of the function. Null if not found.
     */
    protected function findMethodName($operation, $fieldName) {
        $methodName = $operation . ucfirst($fieldName);
        if (is_callable([$this, $methodName])) {
            return $methodName;
        }
        // Maybe field is plural (i.e. addLine for lines)
        $methodName = substr($methodName, 0, -1);
        if (is_callable([$this, $methodName])) {
            return $methodName;
        }
        // Maybe field is plural with 'e' (i.e. addTax for taxes)
        $methodName = substr($methodName, 0, -1);
        if (is_callable([$this, $methodName])) {
            return $methodName;
        }
        return null;
    }

    /**
     * Call findMethodName and throw a ReflectionException if no method is
     * found.
     */
    protected function findMethodNameOrThrow($operation, $fieldName) {
        $methodName = $this->findMethodName($operation, $fieldName);
        if ($methodName == null) {
            throw new \ReflectionException(sprintf('Method to %s %s on %s was not found', $operation, $fieldName, static::class));
        }
        return $methodName;
    }

    /**
     * Merge the field value from $struct into this record.
     * Fields that are not explicitely present in $struct are left untouched.
     * Id and hasImage are reserved fields and are never merged.
     * @param $struct The associative array of values to merge.
     * @param $field The field name or Field definition.
     * @param $dao The dao to use to look for associations.
     * @throws InvalidFieldException When the value is incompatible or
     * when trying to assign a null value to a non-null field.
     */
    protected function mergeDirectField($struct, $field, $dao) {
        if ($field instanceof \Pasteque\Server\Model\Field\Field) {
            $fieldName = $field->getName();
        } else {
            $fieldName = $field;
        }
        if ($fieldName == 'id' || $fieldName == 'hasImage') {
            // Doctrine loads the id
            // and image cannot be altered outside imageAPI
            return;
        }
        if (array_key_exists($fieldName, $struct)) {
            // Updating the value, check that the new one is correct
            if ($field instanceof \Pasteque\Server\Model\Field\Field) {
                $id = null;
                if (array_key_exists('id', $struct)) {
                    $id = $struct['id'];
                }
                $value = $field->convertField(static::class, $id,
                        $struct[$fieldName]);
                if (!$field->isNullable() && $value === null) {
                    throw new InvalidFieldException(
                                InvalidFieldException::CSTR_NOT_NULL,
                                static::class, $fieldName,
                                $id, null);
                }
            } else {
                $value = $struct[$fieldName];
            }
            $setter = $this->findMethodNameOrThrow('set', $fieldName);
            call_user_func([$this, $setter], $value);
        } else {
            // Keeping the value, check that it is correct (for new records)
            if ($field instanceof \Pasteque\Server\Model\Field\Field) {
                $func = $this->findMethodName('get', $fieldName);
                if ($func !== null) {
                    $value = call_user_func([$this, $func]);
                    if (!$field->isNullable() && !$field->isAutoset()
                            && $value === null) {
                        $id = null;
                        if (array_key_exists('id', $struct)) {
                            $id = $struct['id'];
                        }
                        throw new InvalidFieldException(
                                    InvalidFieldException::CSTR_NOT_NULL,
                                    static::class, $fieldName,
                                    $id, null);
                    }
                }
            }
        }
    }

    /**
     * Merge association values from $struct into this record.
     * Fields that are not explicitely present in $struct are left untouched,
     * arrays are replaced.
     * @param $struct The associative array of values to merge.
     * @param $dao The dao to use to look for associations.
     * @throws InvalidFieldException When trying to assign a null value
     * to a non-null association field (constraint NOT_NULL) or when an
     * association was not found (constraint ASSOCIATION_NOT_FOUND).
     */
    protected function mergeAssociationField($struct, $field, $dao) {
        if (!empty($field['internal'])
                || (!array_key_exists($field['name'], $struct))) {
            // Internal fields cannot come from external data.
            // Values non explicitely set are left untouched.
            return;
        }
        $fieldName = $field['name'];
        // Check for required (not null) and type
        if (empty($field['null'])
                && $struct[$fieldName] === null) {
            throw new InvalidFieldException(
                    InvalidFieldException::CSTR_NOT_NULL,
                    static::class, $field['name'],
                    $this->getId(), null);
        }
        if (!empty($field['array']) && !is_array($struct[$fieldName])) {
            $id = $this->getId();
            throw new InvalidFieldException(InvalidFieldException::CSTR_ARRAY,
                    static::class, $field['name'],
                    $id, null);
        }
        // Get value and assign it.
        $arrayField = !empty($field['array']);
        if ($struct[$fieldName] !== null) {
            $value = $this->readAssociationValue($struct, $dao, $field);
            if ($arrayField && count($struct[$fieldName]) == 0) {
                $clr = $this->findMethodNameOrThrow('clear', $fieldName);
                call_user_func([$this, $clr], null);
            } else if ($arrayField) { // Non empty array
                /* At that point we must remove records that are not listed
                 * in struct and add those that are new.
                 * Unbind/rebind doesn't work because it makes Doctrine
                 * ignore the id. */
                $toAdd = [];
                $toRemove = [];
                // Look for records to remove
                $getter = $this->findMethodNameOrThrow('get', $fieldName);
                $dbValues = call_user_func([$this, $getter]);
                foreach ($dbValues as $dbv) {
                    $found = false;
                    foreach ($value as $v) {
                        if ($dbv->idEquals($v)) {
                            $found = true;
                            continue;
                        }
                    }
                    if (!$found) {
                        $toRemove[] = $dbv;
                    }
                }
                // Look for records to add
                foreach ($value as $v) {
                    $id = $v->getId();
                    if ($id == null) { // New record, add it.
                        $toAdd[] = $v;
                        continue;
                    } else {
                        // For non-incremental id, check if the record
                        // is already loaded or not. Add it if new.
                        $found = false;
                        foreach ($dbValues as $dbv) {
                            if ($dbv->idEquals($v)) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $toAdd[] = $v;
                        }
                    }
                }
                // Add and remove
                $remover = $this->findMethodNameOrThrow('remove',
                        $fieldName);
                foreach ($toRemove as $rm) {
                    call_user_func([$this, $remover], $rm);
                }
                $adder = $this->findMethodNameOrThrow('add', $fieldName);
                foreach ($toAdd as $add) {
                    call_user_func([$this, $adder], $add);
                }
            } else { // Non null single record association
                $setter = $this->findMethodNameOrThrow('set', $fieldName);
                call_user_func([$this, $setter], $value);
            }
        } else { // $struct[$fieldName] is null
            $setter = $this->findMethodNameOrThrow('set', $fieldName);
            call_user_func([$this, $setter], null);
        }
    }

    /**
     * Merge the values from $struct into this record.
     * Fields that are not explicitely present in $struct are left untouched,
     * arrays are replaced. It assumes the record is complete after merging.
     * Id and hasImage are reserved fields and are never merged.
     * @param $struct The associative array of values to merge.
     * @param $dao The dao to use to look for associations.
     * @throws InvalidFieldException When trying to assign a null value
     * to a non-null association field or when no value is set (constraint
     * NOT_NULL) or when an association was not found (constraint
     * ASSOCIATION_NOT_FOUND). The key is not set, subclasses should set it.
     */
    public function merge($struct, $dao) {
        // Merge direct fields.
        foreach (static::getDirectFieldNames() as $field) {
            $this->mergeDirectField($struct, $field, $dao);
        }
        // Merge association fields
        $associationFields = static::getAssociationFields();
        foreach ($associationFields as $field) {
            $this->mergeAssociationField($struct, $field, $dao);
        }
    }

    /**
     * Get an associated record to link it to it's parent.
     * @param $struct The parent data as associative array.
     * @param $dao The DAO to use.
     * @param $field The field description of the association (from
     * getAssociationFields).
     * @return The associated record or an array of associated records.
     * Embedded records are already merged.
     * @throws InvalidFieldException when an associated record cannot be
     * found with the given id (only for non-embedded records).
     */
    protected function readAssociationValue($struct, $dao, $field) {
        if (!empty($field['array'])) {
            $subrecords = new \Doctrine\Common\Collections\ArrayCollection();
            foreach ($struct[$field['name']] as $content) {
                if (!empty($field['embedded'])) {
                    $load = new \ReflectionMethod($field['class'],
                            'loadOrCreate');
                    $subrecord = $load->invoke(null, $content, $this, $dao);
                    $subrecord->merge($content, $dao);
                } else {
                    $load = new \ReflectionMethod($field['class'],
                            'loadFromId');
                    $subrecord = $load->invoke(null, $content, $dao);
                    if ($submodel === null) {
                       throw new InvalidFieldException(
                                InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                                static::class, $field['name'],
                                null, $content);
                    }
                }
                $subrecords->add($subrecord);
            }
            return $subrecords;
        } else {
            if (!empty($field['embedded'])) {
                $load = new \ReflectionMethod($field['class'], 'loadOrCreate');
                $subrecord = $load->invoke(null, $struct[$field['name']],
                        $this, $dao);
                $subrecord->merge($struct[$field['name']]);
            } else {
                $load = new \ReflectionMethod($field['class'], 'loadFromId');
                $subrecord = $load->invoke(null, $struct[$field['name']], $dao);
                if ($subrecord === null) {
                   throw new InvalidFieldException(
                            InvalidFieldException::CSTR_ASSOCIATION_NOT_FOUND,
                            static::class, $field['name'],
                            null, $struct[$field['name']]);
                }
            }
            return $subrecord;
        }
    }

    /**
     * Assuming $o is of the same class, check if $this and $o shares the
     * same value for field $field.
     */
    protected function directFieldEquals($o, $field) {
        $fieldName = $field;
        if ($field instanceof \Pasteque\Server\Model\Field\Field) {
            $fieldName = $field->getName();
        }
        $getter = $this->findMethodNameOrThrow('get', $fieldName);
        $thisVal = call_user_func([$this, $getter]);
        $oVal = call_user_func([$o, $getter]);
        if ($field instanceof \Pasteque\Server\Model\Field\Field) {
            return $field->areEqual($thisVal, $oVal);
        }
        if ($thisVal === null || $oVal === null) {
            return $thisVal == $oVal;
        }
        return $thisVal == $oVal;
    }

    /**
     * Assuming $o is of the same class, check if $this and $o shares the
     * same association values for field $field. The values of the associated
     * records are compared.
     */
    protected function associationEquals($o, $field) {
        $isArray = !empty($field['array']);
        $getter = $this->findMethodNameOrThrow('get', $field['name']);
        $thisVal = call_user_func([$this, $getter]);
        $oVal = call_user_func([$o, $getter]);
        if ($thisVal === null || $oVal === null) {
            return $thisVal === $oVal;
        }
        if (!$isArray) {
            return ($thisVal->equals($oVal));
        } else {
            if (count($thisVal) != count($oVal)) {
                return false;
            }
            for ($i = 0; $i < count($thisVal); $i++) {
                $thisSubVal = $thisVal[$i];
                $oSubVal = $oVal[$i];
                if ($thisSubVal === null || $oSubVal === null) {
                    if ($thisSubVal != $oSubVal) {
                        return false;
                    }
                } else {
                    if (!$thisSubVal->equals($oSubVal)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Check if this record has the same values as the other.
     * It should compare that $this and $o are of the same class and that
     * every relevant fields share the same values.
     */
    public abstract function equals($o);
}
