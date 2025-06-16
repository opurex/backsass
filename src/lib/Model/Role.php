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

use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Role
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="roles")
 */
class Role extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [new StringField('name'), 'permissions'];
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
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $name;
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }

    /** List of permissions. It is stored in database in a single string,
     * values separated by ;. Used as an array once retreived from database.
     * @var text
     * @SWG\Property()
     * @Column(type="text", nullable=false)
     */
    protected $permissions = '';
    /** Get the array of permissions.
     * A permission cannot contain the character ';' */
    public function getPermissions() { return explode(';', $this->permissions); }
    /** Set the array of permissions.
     * A permission cannot contain the character ';' */
    public function setPermissions($permissions) {
        $this->permissions = implode(';', $permissions);
    }
    /** Add a single permission to the list, if not already present.
     * A permission cannot contain the character ';' */
    public function addPermission($permission) {
        $permissions = $this->getPermissions();
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->setPermissions($permissions);
        }
    }

}
