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

use \Pasteque\Server\Model\User;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class UserTest extends TestCase
{
    private $dao;

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testAuthenticateEmpty() {
        $user = new User();
        $user->setPassword(null);
        $this->assertTrue($user->authenticate(null));
        $this->assertTrue($user->authenticate(''));
        $this->assertTrue($user->authenticate('empty:blablabla'));
        $user->setPassword('');
        $this->assertTrue($user->authenticate(null));
        $this->assertTrue($user->authenticate(''));
        $this->assertTrue($user->authenticate('empty:blablabla'));
        $user->setPassword('empty:');
        $this->assertTrue($user->authenticate(null));
        $this->assertTrue($user->authenticate(''));
        $this->assertTrue($user->authenticate('empty:blablabla'));
        $user->setPassword('empty:ishouldbeemptybutno');
        $this->assertTrue($user->authenticate(null));
        $this->assertTrue($user->authenticate(''));
        $this->assertTrue($user->authenticate('empty:blablabla'));
    }

    public function testAuthenticateSHA1() {
        $pwd = 'pasteque';
        $hash = sha1($pwd);
        $user = new User();
        $user->setPassword(sprintf('sha1:%s', $hash));
        $this->assertTrue($user->authenticate($pwd));
        $this->assertFalse($user->authenticate($hash));
        $this->assertTrue($user->authenticate(sprintf('sha1:%s', $hash)));
        $this->assertFalse($user->authenticate(null));
        $this->assertFalse($user->authenticate(''));
    }

    public function testAuthenticatePlain() {
        $pwd = 'pasteque';
        $user = new User();
        $user->setPassword(sprintf('plain:%s', $pwd));
        $this->assertTrue($user->authenticate($pwd));
        $this->assertTrue($user->authenticate(sprintf('plain:%s', $pwd)));
        $this->assertFalse($user->authenticate('PafPasteque'));
        $this->assertFalse($user->authenticate(null));
        $this->assertFalse($user->authenticate(''));
    }

    public function testAuthenticateClear() {
        $pwd = 'pasteque';
        $user = new User();
        $user->setPassword($pwd);
        $this->assertTrue($user->authenticate($pwd));
        $this->assertFalse($user->authenticate('PafPasteque'));
        $this->assertFalse($user->authenticate(null));
        $this->assertFalse($user->authenticate(''));
    }

}
