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

use \Pasteque\Server\Model\Role;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpRoleTest extends TestCase
{
    private $curl;
    private static $token;
    private $dao;

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
        $this->dao->commit();
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        $all = $this->dao->search(Role::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetAll() {
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/role/getAll'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertEquals(1, count($data));
        $this->assertEquals($this->role->getName(), $data[0]['name']);
        $this->assertEquals($this->role->getId(), $data[0]['id']);
    }

    public function testPostNew() {
        $newRole = new Role();
        $newRole->setName('New Role');
        $newRole->addPermission('Perm1');
        $postData = $newRole->toStruct();
        unset($postData['id']);
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/role'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbRoles = $this->dao->search(Role::class);
        $this->assertEquals(2, count($dbRoles));
        $found = false;
        foreach ($dbRoles as $dbRole) {
            if ($dbRole->getId() != $this->role->getId()) {
                $snapRole = $this->dao->readSnapshot(Role::class, $dbRole->getId());
                $this->assertEquals('New Role', $snapRole->getName());
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testPostUpdate() {
        $struct = $this->role->toStruct();
        $struct['name'] = 'Edited Role';
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/role'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbRoles = $this->dao->search(Role::class);
        $this->assertEquals(1, count($dbRoles));
        $snapRole = $this->dao->readSnapshot(Role::class, $dbRoles[0]->getId());
        $this->assertEquals('Edited Role', $snapRole->getName());
    }
}
