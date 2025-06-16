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

use \Pasteque\Server\API\CustomerAPI;
use \Pasteque\Server\Model\Customer;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class CustomerAPITest extends TestCase
{
    private $dao;
    private $api;

    protected function setUp(): void {
        global $dbInfo;
        $this->dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $this->api = new CustomerAPI($this->dao);
    }

    protected function tearDown(): void {
        $all = $this->dao->search(Customer::class);
        foreach($all as $record) {
            $this->dao->delete($record);
        }
        $this->dao->commit();
        $this->dao->close();
    }

    /** Create a customer with a preset balance with write and
     * check that the balance is still 0. */
    public function testWriteCreateBalance() {
        $c = new Customer();
        $c->setDispName("Customer");
        $c->setBalance(10.0);
        $this->api->write($c);
        $snapshot = $this->dao->readSnapshot(Customer::class, $c->getId());
        $this->assertNotNull($c);
        $this->assertEquals(0.0, $snapshot->getBalance());
    }

    /** @depends testWriteCreateBalance
     * Check that writing a customer with an existing balance keeps
     * the previous balance value. */
    public function testWriteUpdateBalance() {
        $c = new Customer();
        $c->setDispName("Customer");
        $c->setBalance(10.0);
        $this->dao->write($c); // force balance by skipping API
        $this->dao->commit();
        $c->setBalance(20.0);
        $this->api->write($c);
        $snapshot = $this->dao->readSnapshot(Customer::class, $c->getId());
        $this->assertNotNull($c);
        $this->assertEquals(10.0, $snapshot->getBalance());
    }

    public function testSetBalance() {
        $c = new Customer();
        $c->setDispName("Customer");
        $c->setBalance(10.0);
        $this->dao->write($c); // force balance by skipping API
        $this->dao->commit();
        $this->api->setBalance($c->getId(), 20.0);
        $snapshot =  $this->dao->readSnapshot(Customer::class, $c->getId());
        $this->assertNotNull($snapshot);
        $this->assertEquals(20.0, $snapshot->getBalance());
    }

}
