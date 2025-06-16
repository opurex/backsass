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

use \Pasteque\Server\System\DAO\DAOCondition;


/** CRUD API for User. */
class UserAPI extends APIHelper implements API
{
    const MODEL_NAME = 'Pasteque\Server\Model\User';
    const DEFAULT_ORDER = 'name';

    public function getByName($name)
    {
        global $ptApp;

        $data = $this->dao->search(static::MODEL_NAME, new DAOCondition('name', '=', $name), 1);
        if (count($data) > 0) {
            return $data[0];
        }
        return null;
    }

    public function write($data) {
        $this->supportOrDie($data);
        $arrayArgs = is_array($data);
        $data = ($arrayArgs) ? $data : array($data);
        foreach ($data as $user) {
            if ($user->getId() == null) {
                // Allow to set password for a new user
                $hash = $user->getPassword();
                if (substr($hash, 0, 5) != 'sha1:') {
                    $hash = sprintf('sha1:%s', sha1($hash));
                }
                $user->setPassword($hash);
            } else {
                // Prevent editing the password
                // TODO: do not edit
            }
        }
        if ($arrayArgs) {
            return parent::write($data);
        } else {
            return parent::write($data[0]);
        }
    }

    /** Update user's password. Null and empty string are considered
     * as no password.
     * @param \Pasteque\Server\Model\User $user The user.
     * @param string $oldPassword Clear old password or encrypted
     * with 'sha1:' prefix.
     * @param string $newPassword Clear new password or encrypted
     * with 'sha1:' prefix.
     * @return True on success, false otherwise. $user is updated.
     */
    public function updatePassword($user, $oldPassword, $newPassword) {
        if (!$user->authenticate($oldPassword)) {
            return false;
        }
        // Update
        if ($newPassword === null || $newPassword == '') {
            $user->setPassword(null);
        } else {
            // Pasteque desktop to-be-deprecated special values
            if (substr($newPassword, 0, 6) == 'empty:') {
                $newPassword = '';
            } else if (substr($newPassword, 0, 6) == 'plain:') {
                $newPassword = substr($newPassword, 6);
            }
            // SHA1 encryption
            $hash = $newPassword;
            if (substr($newPassword, 0, 5) != 'sha1:') {
                $hash = sprintf('sha1:%s', sha1($newPassword));
            }
            $user->setPassword($hash);
        }
        $this->dao->write($user);
        $this->dao->commit();
        return true;
    }

}
