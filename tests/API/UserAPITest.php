<?php
//    Pasteque server testing
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.
namespace Pasteque\Server;

use \Pasteque\Server\API\UserAPI;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class UserAPITest extends TestCase
{
    private $dao;
    private $api;
    private $role;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new UserAPI($this->dao);
        $this->role = new Role();
        $this->role->setName('Role');
        $this->dao->write($this->role);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        $users = $this->dao->search(User::class);
        foreach ($users as $record) {
            $this->dao->delete($record);
        }
        $this->dao->delete($this->role);
        $this->dao->commit();
        $this->dao->close();
    }

    public function testSetPassword() {
        $user = new User();
        $user->setName('User');
        $user->setRole($this->role);
        $this->dao->write($user);
        $this->dao->commit();
        $this->api->updatePassword($user, '', 'pasteque');
        $read = $this->dao->readSnapshot(User::class, $user->getId());
        $this->assertTrue($read->authenticate('pasteque'));
    }

    /** @depends testSetPassword */
    public function testUpdatePasswordNok() {
        $user = new User();
        $user->setName('User');
        $user->setRole($this->role);
        $this->dao->write($user);
        $this->dao->commit();
        $this->api->updatePassword($user, '', 'pasteque');
        $this->assertFalse($this->api->updatePassword($user, 'iforgotthis', 'newPass'));
        $read = $this->dao->readSnapshot(User::class, $user->getId());
        $this->assertFalse($read->authenticate('newPass'));
        $this->assertTrue($read->authenticate('pasteque'));
    }

    /** @depends testSetPassword */
    public function testUpdatePasswordOk() {
        $user = new User();
        $user->setName('User');
        $user->setRole($this->role);
        $this->dao->write($user);
        $this->dao->commit();
        $this->api->updatePassword($user, '', 'pasteque');
        $this->assertTrue($this->api->updatePassword($user, 'pasteque', 'newPass'));
        $read = $this->dao->readSnapshot(User::class, $user->getId());
        $this->assertTrue($read->authenticate('newPass'));
        $this->assertFalse($read->authenticate('pasteque'));
    }

    /** @depends testSetPassword */
    public function testUpdatePasswordEncrypted() {
        $user = new User();
        $user->setName('User');
        $user->setRole($this->role);
        $this->dao->write($user);
        $this->dao->commit();
        $this->api->updatePassword($user, '', 'pasteque');
        $read = $this->dao->readSnapshot(User::class, $user->getId());
        $this->assertEquals(sprintf('sha1:%s', sha1('pasteque')), $read->getPassword());
    }

    /** @depends testSetPassword */
    public function testRemovePasswordEmpty() {
        $user = new User();
        $user->setName('User');
        $user->setRole($this->role);
        $this->dao->write($user);
        $this->dao->commit();
        $this->api->updatePassword($user, '', 'pasteque');
        $this->assertTrue($this->api->updatePassword($user, 'pasteque', ''));
        $read = $this->dao->readSnapshot(User::class, $user->getId());
        $this->assertTrue($read->authenticate(null));
    }

    /** @depends testSetPassword */
    public function testRemovePasswordNull() {
        $user = new User();
        $user->setName('User');
        $user->setRole($this->role);
        $this->dao->write($user);
        $this->dao->commit();
        $this->api->updatePassword($user, '', 'pasteque');
        $this->assertTrue($this->api->updatePassword($user, 'pasteque', null));
        $read = $this->dao->readSnapshot(User::class, $user->getId());
        $this->assertTrue($read->authenticate(null));
    }
}
