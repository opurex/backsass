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

use \Pasteque\Server\API\PlaceAPI;
use \Pasteque\Server\Model\Floor;
use \Pasteque\Server\Model\Place;
use \Pasteque\Server\System\Login;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class HttpPlaceTest extends TestCase
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
        foreach ([Place::class, Floor::class] as $class) {
            $all = $this->dao->search($class);
            foreach($all as $record) {
                $this->dao->delete($record);
            }
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testWrite() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $place = new Place();
        $place->setLabel('Place');
        $floor->addPlace($place);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/places'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode([$floor->toStruct()]));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResponse = json_decode($resp, true);
        $this->assertNotNull($jsResponse, 'Could not parse response');
        $this->assertEquals(1, count($jsResponse), 'Floor count mismatch');
        $floorId = $jsResponse[0]['id'];
        $floorSnapshot = $this->dao->readSnapshot(Floor::class, $floorId);
        $this->assertNotNull($floorSnapshot, 'Floor was not inserted');
        $this->assertEquals(1, count($jsResponse[0]['places']));
        $this->assertNotNull($jsResponse[0]['places'][0]['id']);
    }

    /** @depends testWrite */
    public function testUpdate() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $place = new Place();
        $place->setLabel('Place');
        $floor->addPlace($place);
        $api = new PlaceAPI($this->dao);
        $api->write($floor);
        $id = $floor->getId();
        $place2 = new Place();
        $place2->setLabel('Place2');
        $floor->clearPlaces();
        $floor->addPlace($place2);
        $floor->addPlace($place);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_URL, apiUrl('api/places'));
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
                json_encode([$floor->toStruct()]));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,
                [Login::TOKEN_HEADER . ': ' . static::$token,
                        'Content-Type: application/json']);
        $resp = curl_exec($this->curl);
        $this->assertEquals(200, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $jsResponse = json_decode($resp, true);
        $this->assertNotNull($jsResponse, 'Could not parse response');
        $this->assertEquals(1, count($jsResponse), 'Floor count mismatch');
        $floorId = $jsResponse[0]['id'];
        $this->assertEquals($id, $floorId, 'Floor id was changed');
        $floorSnapshot = $this->dao->readSnapshot(Floor::class, $floorId);
        $this->assertEquals(2, count($floorSnapshot->getPlaces()),
                'Unmatching places count in database');
        $this->assertEquals(2, count($jsResponse[0]['places']),
                'Unmatching places count in response');
        foreach ($jsResponse[0]['places'] as $place) {
            $this->assertNotNull($place['id']);
        }
    }

}
