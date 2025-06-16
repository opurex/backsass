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

namespace Pasteque\Server\Model;

use \Pasteque\Server\Model\Field\BoolField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Option. Non-structured general options.
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="options")
 */
class Option extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        // 'key' and 'value' are keywords in mysql and crash Doctrine
        return [
                new StringField('name'),
                new BoolField('system'),
                new StringField('content', ['length' => null])];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getReferenceKey() {
        return 'name';
    }
    /** Same as getName(). */
    public function getReference() {
        return $this->getName();
    }

    /**
     * @var string
     * @SWG\Property()
     * @Id @Column(type="string")
     */
    protected $name;
    public function getId() { return $this->name; }
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }

    /**
     * Is the option a system-option. A system option requires its
     * dedicated API to be updated, if there is any.
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $system = false;
    public function getSystem() { return $this->system; }
    /** Alias for getSystem (the latter is required for Doctrine) */
    public function isSystem() { return $this->getSystem(); }
    public function setSystem($system) { $this->system = $system; }

    /**
     * Content. A single content or generally a JSON string.
     * @var string|null
     * @SWG\Property()
     * @Column(type="text")
     */
    protected $content = '';
    public function getContent() { return $this->content; }
    public function setContent($content) { $this->content = $content; }

}
