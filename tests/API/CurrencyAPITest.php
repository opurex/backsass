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

use \Pasteque\Server\API\CurrencyAPI;
use \Pasteque\Server\Exception\InvalidFieldException;
use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class CurrencyAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new CurrencyAPI($this->dao);
    }

    protected function tearDown(): void {
        $all = $this->dao->search(Currency::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    public function testGetMain() {
        $main = new Currency();
        $main->setReference('main');
        $main->setLabel('Main');
        $main->setMain(true);
        $other = new Currency();
        $other->setReference('other');
        $other->setLabel('Other');
        $this->dao->write($main);
        $this->dao->write($other);
        $this->dao->commit();
        $read = $this->api->getMain();
        $this->assertEquals($main->getReference(), $read->getReference());
    }

    public function testUpdateMain() {
        $main = new Currency();
        $main->setReference('main');
        $main->setLabel('Main');
        $main->setMain(true);
        $other = new Currency();
        $other->setReference('other');
        $other->setLabel('Other');
        $this->dao->write($main);
        $this->dao->write($other);
        $this->dao->commit();
        $other->setMain(true);
        $this->api->write($other);
        $readMain = $this->api->getMain();
        $this->assertEquals($other->getReference(), $readMain->getReference());
        $readOther = $this->api->get($main->getId());
        $this->assertFalse($readOther->isMain());
    }

    public function testDeleteMain() {
        $main = new Currency();
        $main->setReference('main');
        $main->setLabel('Main');
        $main->setMain(true);
        $other = new Currency();
        $other->setReference('other');
        $other->setLabel('Other');
        $this->dao->write($main);
        $this->dao->write($other);
        $this->dao->commit();
        $exceptionThrown = false;
        try {
            $this->api->delete($main->getId());
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidFieldException::CSTR_DEFAULT_REQUIRED,
                    $e->getConstraint());
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testUnsetMain() {
        $main = new Currency();
        $main->setReference('main');
        $main->setLabel('Main');
        $main->setMain(true);
        $other = new Currency();
        $other->setReference('other');
        $other->setLabel('Other');
        $this->dao->write($main);
        $this->dao->write($other);
        $this->dao->commit();
        $main->setMain(false);
        $exceptionThrown = false;
        try {
            $this->api->write($main);
        } catch (InvalidFieldException $e) {
            $exceptionThrown = true;
            $this->assertEquals(InvalidFieldException::CSTR_DEFAULT_REQUIRED,
                    $e->getConstraint());
        }
        $this->assertTrue($exceptionThrown);
    }
}
