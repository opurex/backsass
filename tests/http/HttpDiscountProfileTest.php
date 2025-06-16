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

use \Pasteque\Server\Model\DiscountProfile;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOCondition;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpDiscountProfileTest extends TestCase
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
    }

    protected function tearDown(): void {
        curl_close($this->curl);
        $all = $this->dao->search(DiscountProfile::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetAll() {
        $dp = new DiscountProfile();
        $dp->setLabel('dp');
        $dp->setRate(0.1);
        $this->dao->write($dp);
        $this->dao->commit();
        curl_setopt($this->curl, CURLOPT_URL,
                apiUrl('api/discountprofile/getAll'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $data = json_decode($resp, true);
        $this->assertEquals(1, count($data));
        $this->assertEquals($dp->getLabel(), $data[0]['label']);
        $this->assertEquals($dp->getRate(), $data[0]['rate']);
    }

    public function testPostNew() {
        $struct = ['label' => 'dp', 'rate' => 0.2];
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/discountprofile'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbDp = $this->dao->search(DiscountProfile::class);
        $this->assertEquals(1, count($dbDp));
        $this->assertEquals('dp', $dbDp[0]->getLabel());
        $this->assertEquals(0.2, $dbDp[0]->getRate());
    }

    public function testPostUpdate() {
        $dp = new DiscountProfile();
        $dp->setLabel('dp');
        $dp->setRate(0.2);
        $this->dao->write($dp);
        $this->dao->commit();
        $struct = $dp->toStruct();
        $struct['label'] = 'edited';
        $struct['rate'] = 0.1;
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/discountprofile'));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($struct));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $dbDp = $this->dao->search(DiscountProfile::class);
        $this->assertEquals(1, count($dbDp));
        $snapDp = $this->dao->readSnapshot(DiscountProfile::class,
                $dbDp[0]->getId());
        $this->assertEquals('edited', $snapDp->getLabel());
        $this->assertEquals(0.1, $snapDp->getRate());
    }
}
