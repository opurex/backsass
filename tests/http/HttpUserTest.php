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
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpUserTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;
    private $role;
    private $user;

    public static function setUpBeforeClass(): void {
        static::$token = obtainToken();
    }

    public static function tearDownAfterClass(): void {
    }

    protected function setUp(): void {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token]);
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->role = new Role();
        $this->role->setName('Test Role');
        $this->role->setPermissions(['perm1', 'perm2']);
        $this->dao->write($this->role);
        $this->user = new User();
        $this->user->setName('Test User');
        $this->user->setRole($this->role);
        $this->dao->write($this->user);
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        foreach ([User::class, Role::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetAll() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/getAll'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertEquals(1, count($data));
        $this->assertEquals($this->user->getName(), $data[0]['name']);
        $this->assertEquals($this->role->getId(), $data[0]['role']);
    }

    public function testUpdatePasswordEmpty() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/' . $this->user->getId() . '/password'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['oldPassword' => 'anyPassword', 'newPassword' => 'new']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(true, json_decode($resp));
        $dbUser = $this->dao->readSnapshot(User::class, $this->user->getId());
        $this->assertNotEquals(null, $dbUser->getPassword());
        $this->assertEquals(true, $dbUser->authenticate('new'));
    }

    /** @depends testUpdatePasswordEmpty */
    public function testUpdatePasswordInvalid() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/' . $this->user->getId() . '/password'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['oldPassword' => 'anyPassword', 'newPassword' => 'new']);
        $resp = curl_exec($this->curl);
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/' . $this->user->getId() . '/password'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['oldPassword' => 'notThatPassword', 'newPassword' => 'anotherOne']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(false, json_decode($resp));
        $dbUser = $this->dao->readSnapshot(User::class, $this->user->getId());
        $this->assertTrue($dbUser->authenticate('new'));
    }

    /** @depends testUpdatePasswordEmpty */
    public function testUpdatePasswordValid() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/' . $this->user->getId() . '/password'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['oldPassword' => '', 'newPassword' => 'new']);
        $resp = curl_exec($this->curl);
        // Update password
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/' . $this->user->getId() . '/password'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['oldPassword' => 'new', 'newPassword' => 'new2']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(true, json_decode($resp));
        $dbUser = $this->dao->readSnapshot(User::class, $this->user->getId());
        $this->assertTrue($dbUser->authenticate('new2'));
        // Clear password
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user/' . $this->user->getId() . '/password'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                ['oldPassword' => 'new2', 'newPassword' => '']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->assertEquals(true, json_decode($resp));
        $dbUser = $this->dao->readSnapshot(User::class, $this->user->getId());
        $this->assertEquals(null, $dbUser->getPassword());
    }

    public function testPostNew() {
        $newUser = new User();
        $newUser->setName('New User');
        $newUser->setRole($this->role);
        $postData = $newUser->toStruct();
        unset($postData['id']);
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbUser = $this->dao->search(User::class,
                new DAOCondition('name', '=', 'New User'));
        $this->assertEquals(1, count($dbUser));
    }

    public function testPostUpdate() {
        $this->user->setName('Edited User');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->user->toStruct());
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbUser = $this->dao->search(User::class);
        $this->assertEquals(1, count($dbUser));
        $this->assertEquals('Edited User', $dbUser[0]->getName());
    }

    public function testPostInvalidRole() {
        $newUser = new User();
        $newUser->setName('New User');
        $newUser->setRole($this->role);
        $postData = $newUser->toStruct();
        unset($postData['id']);
        $postData['role'] = $this->role->getId() + 1;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/user'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        $resp = curl_exec($this->curl);
        $this->assertEquals(400, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResp = json_decode($resp, true);
        $this->assertNotEquals(false, $jsResp);
        $this->assertEquals('InvalidField', $jsResp['error']);
        $this->assertEquals('AssociationNotFound', $jsResp['constraint']);
        $this->assertEquals(User::class, $jsResp['class']);
        $this->assertEquals('role', $jsResp['field']);
        $this->assertEquals($this->role->getId() + 1, $jsResp['value']);
    }
}
