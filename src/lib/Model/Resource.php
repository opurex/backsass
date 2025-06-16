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

namespace Pasteque\Server\Model;

use \Pasteque\Server\Model\Field\EnumField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Resource. Legacy class used while there are still resources in use.
 * Please do not create new resources.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="resources")
 */
class Resource extends DoctrineMainModel
{
    const TYPE_TEXT = 0;
    const TYPE_IMAGE = 1;
    const TYPE_BIN = 2;

    protected static function getDirectFieldNames() {
        return [
                new StringField('label'),
                new EnumField('type', ['values' => [static::TYPE_TEXT,
                        static::TYPE_IMAGE, static::TYPE_BIN]]),
                'content'
                ];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getReferenceKey() {
        return 'label';
    }
    /** Same as getName(). */
    public function getReference() {
        return $this->getLabel();
    }

    public function getId() { return $this->getLabel(); }

    /**
     * Name of the resource
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     * @Id
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    /**
     * Type of the resource. See constants.
     * @var int resType
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    protected $type = 0;
    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; }

    /**
     * Content
     * @var binary
     * @SWG\Property()
     * @Column(type="blob")
     */
    protected $content;
    public function getContent() {
        if (gettype($this->content) == 'resource') {
            // Doctrine returns a stream when reading from database.
            return stream_get_contents($this->content);
        } else {
            // When using setContent it has it's actual value.
            return $this->content;
        }
    }
    public function setContent($content) { $this->content = $content; }

    public function toStruct() {
        // Handle base64 encoding of binary bin and not binary text
        $struct = ['label' => $this->getLabel(), 'type' => $this->getType()];
        switch ($this->getType()) {
            case static::TYPE_TEXT:
                $struct['content'] = $this->getContent();
                break;
            case static::TYPE_IMAGE:
            case static::TYPE_BIN:
            default:
                // It is safer to base64encode because json_encode
                // may crash everything with binary
                $struct['content'] = base64_encode($this->getContent());
                break;
        }
        return $struct;
    }

    public function merge($struct, $dao) {
        // Convert incoming base64 data into binary before reading
        $newStruct = ['label' => $struct['label'], 'type' => $struct['type']];
        if ($newStruct['type'] == static::TYPE_IMAGE
                || $newStruct['type'] == static::TYPE_BIN) {
            $newStruct['content'] = base64_decode($struct['content']);
        } else {
            $newStruct['content'] = $struct['content'];
        }
        parent::merge($newStruct, $dao);
    }
}
