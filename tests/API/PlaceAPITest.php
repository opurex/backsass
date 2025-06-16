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
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class PlaceAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new PlaceAPI($this->dao);
    }

    protected function tearDown(): void {
        $places = $this->dao->search(Place::class);
        foreach ($places as $record) {
            $this->dao->delete($record);
        }
        $floors = $this->dao->search(Floor::class);
        foreach ($floors as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    /** Create and read an empty Floor */
    public function testEmpty() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $this->api->write($floor);
        $read = $this->dao->readSnapshot(Floor::class, $floor->getId());
        $this->assertEquals($floor->getLabel(), $read->getLabel());
        $this->assertEquals(0, count($read->getPlaces()));
    }

    public function testPlace() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $place = new Place();
        $place->setLabel('Place');
        $place->setX(10);
        $place->setY(5);
        $floor->addPlace($place);
        $this->api->write($floor);
        $read = $this->dao->readSnapshot(Floor::class, $floor->getId());
        $this->assertEquals($floor->getLabel(), $read->getLabel());
        $this->assertEquals(1, count($read->getPlaces()));
        $this->assertEquals(10, $read->getPlaces()->get(0)->getX());
        $this->assertEquals(5, $read->getPlaces()->get(0)->getY());
    }

    /** @depends testPlace */
    public function testDeleteCascade() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $place = new Place();
        $place->setLabel('Place');
        $place->setX(10);
        $place->setY(5);
        $floor->addPlace($place);
        $this->dao->write($floor);
        $this->dao->commit();
        $floorId = $floor->getId();
        $placeId = $place->getId();
        $count = $this->api->delete($floorId);
        $this->assertEquals(1, $count);
        $read = $this->dao->readSnapshot(Floor::class, $placeId);
        $this->assertNull($read);
    }

    /** @depends testDeleteCascade */
    public function testReplace() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $place = new Place();
        $place->setLabel('Place');
        $place->setX(10);
        $place->setY(5);
        $floor->addPlace($place);
        $this->dao->write($floor);
        $this->dao->commit();
        $replaceFloor = Floor::loadFromId($floor->getId(), $this->dao);
        $replaceFloor->merge(['label' => 'replaced',
                'places' => [['label' => 'place2', 'x' => 30, 'y' => 20]]],
                $this->dao);
        $this->api->write($replaceFloor);
        $readFloor = $this->dao->readSnapshot(Floor::class, $floor->getId());
        $allFloors = $this->dao->search(Floor::class);
        $this->assertEquals(1, count($allFloors),
                'Floor was inserted instead of updated');
        $this->assertEquals($floor->getId(), $readFloor->getId());
        $this->assertEquals('replaced', $readFloor->getLabel());
        $allPlaces = $this->dao->search(Place::class);
        $this->assertEquals(1, count($allPlaces),
                'Places were inserted instead of replaced');
        $this->assertEquals(1, $readFloor->getPlaces()->count(),
                'New place was not attached to the updated floor');
        $place = $readFloor->getPlaces()->get(0);
        $this->assertEquals('place2', $place->getLabel());
        $this->assertEquals(30, $place->getX());
        $this->assertEquals(20, $place->getY());
    }

    /** @depends testReplace */
    public function testAdd() {
        $floor = new Floor();
        $floor->setLabel('Floor');
        $place = new Place();
        $place->setLabel('Place');
        $place->setX(10);
        $place->setY(5);
        $floor->addPlace($place);
        $this->dao->write($floor);
        $this->dao->commit();
        $place2 = new Place();
        $place2->setLabel('Place2');
        $place2->setX(20);
        $place2->setY(25);
        $floor->addPlace($place2);
        $this->api->write($floor);
        $readFloor = $this->dao->readSnapshot(Floor::class, $floor->getId());
        $allFloors = $this->dao->search(Floor::class);
        $this->assertEquals(1, count($allFloors),
                'Floor was inserted instead of updated');
        $this->assertEquals($floor->getId(), $readFloor->getId());
        $allPlaces = $this->dao->search(Place::class);
        $this->assertEquals(2, count($allPlaces),
                            'Places update failed');
        $this->assertEquals(2, $readFloor->getPlaces()->count(),
                'New place was not attached to the updated floor');
        $foundPlace1 = false;
        $foundPlace2 = false;
        foreach($allPlaces as $p) {
            if ($p->getLabel() == $place->getLabel()) {
                $foundPlace1 = true;
                $this->assertEquals($place->getX(), $p->getX());
                $this->assertEquals($place->getY(), $p->getY());
            } elseif ($p->getLabel() == $place2->getLabel()) {
                $foundPlace2 = true;
                $this->assertEquals($place2->getX(), $p->getX());
                $this->assertEquals($place2->getY(), $p->getY());
            }
        }
        $this->assertTrue($foundPlace1 && $foundPlace2);
    }
}
