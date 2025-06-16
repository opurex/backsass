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

use \Pasteque\Server\Model\Field\BoolField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class User
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="users")
 */
class User extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [
                new StringField('name'),
                'password', // not StringField because nullable (legacy)
                new BoolField('active'),
                'hasImage',
                'card' // not StringField because nullable (legacy)
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'role',
                 'class' => '\Pasteque\Server\Model\Role'
                 ]
                ];
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

    /** User password.
     * @var string
     * @SWG\Property()
     * @Column(type="string", nullable=true)
     */
    protected $password;
    public function getPassword() { return $this->password; }
    /** Low-level password set.
     * Use UserAPI->setPassword instead to add encryption. */
    public function setPassword($password) { $this->password = $password; }

    /**
     * ID of the assigned role
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="Role")
     * @JoinColumn(name="role_id", referencedColumnName="id", nullable=false)
     */
    protected $role;
    public function getRole() { return $this->role; }
    public function setRole($role) { $this->role = $role; }

    /**
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $active = true;
    public function getActive() { return $this->active; }
    public function isActive() { return $this->getActive(); }
    public function setActive($active) { $this->active = $active; }

    /**
     * True if an image can be found for this model.
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $hasImage = false;
    public function getHasImage() { return $this->hasImage; }
    public function hasImage() { return $this->getHasImage(); }
    public function setHasImage($hasImage) { $this->hasImage = $hasImage; }

    /**
     * Code of User's card
     * @var string
     * @SWG\Property()
     * @Column(type="string", nullable=true)
     */
    protected $card;
    public function getCard() { return $this->card; }
    public function setCard($card) { $this->card = $card; }

    public function authenticate($password) {
        if (substr($password, 0, 6) == 'empty:') {
            // Empty is always empty
            $password = '';
        } else if (substr($password, 0, 6) == 'plain:') {
            // Remove no-encryption prefix not to care about it later
            $password = substr($password, 6);
        }
        $currPwd = $this->getPassword();
        if ($currPwd === null || $currPwd == ""
                || substr($currPwd, 0, 6) == "empty:") {
            // No password
            return true;
        } else if (substr($currPwd, 0, 5) == "sha1:") {
            // SHA1 encryption
            if (substr($password, 0, 5) == 'sha1:') {
                $hash = $password;
            } else {
                $hash = 'sha1:' . sha1($password);
            }
            return ($currPwd == $hash);
        } else if (substr($currPwd, 0, 6) == "plain:") {
            // Clear password (legacy)
            return ($currPwd == "plain:" . $password);
        } else {
            // Default clear password (legacy)
            return ($currPwd == $password);
        }
    }
}
