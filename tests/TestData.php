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

namespace Pasteque\Server;

use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\CashRegister;
use \Pasteque\Server\Model\CashSession;
use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\Model\Option;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Product;
use \Pasteque\Server\Model\Role;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\TariffArea;
use \Pasteque\Server\Model\User;

/** Sample data to init/destroy test data. */
class TestData
{
    private $tax;
    private $cat;
    private $prd;
    private $cash;
    private $session;
    private $pm;
    private $curr;
    private $role;
    private $user;
    private $option;

    public function __construct() {
    }

    public function install($dao) {
        $this->cat = new Category();
        $this->cat->setReference('category');
        $this->cat->setLabel('Category');
        $dao->write($this->cat);
        $this->tax= new Tax();
        $this->tax->setLabel('VAT');
        $this->tax->setRate(0.1);
        $dao->write($this->tax);
        $this->prd = new Product();
        $this->prd->setReference('product');
        $this->prd->setLabel('Product');
        $this->prd->setTax($this->tax);
        $this->prd->setCategory($this->cat);
        $this->prd->setPriceSell(1.0);
        $dao->write($this->prd);
        $this->pm = new PaymentMode();
        $this->pm->setReference('pm');
        $this->pm->setLabel('Payment mode');
        $dao->write($this->pm);
        $this->curr = new Currency();
        $this->curr->setReference('curr');
        $this->curr->setLabel('Currency');
        $this->curr->setMain(true);
        $dao->write($this->curr);
        $this->cash = new CashRegister();
        $this->cash->setReference('cash');
        $this->cash->setLabel('Cash');
        $dao->write($this->cash);
        $this->role = new Role();
        $this->role->setName('role');
        $dao->write($this->role);
        $this->user = new User();
        $this->user->setName('user');
        $this->user->setRole($this->role);
        $dao->write($this->user);
        $this->session = new CashSession();
        $this->session->setCashRegister($this->cash);
        $this->session->setSequence(1);
        $this->session->setOpenDate(new \DateTime('2018-01-01 8:00'));
        $dao->write($this->session);
        $this->version = new Option();
        $this->version->setName('dblevel');
        $this->version->setSystem(true);
        $dao->write($this->version);
        $this->option = new Option();
        $this->option->setName('option');
        $this->option->setContent('test');
        $dao->write($this->option);
        $dao->commit();
    }

    public function delete($dao) {
        $sessions = $dao->search(CashSession::class);
        $dao->delete($sessions[0]);
        $dao->delete($this->cash);
        $dao->delete($this->user);
        $dao->delete($this->role);
        $dao->delete($this->prd);
        $dao->delete($this->cat);
        $dao->delete($this->tax);
        $dao->delete($this->pm);
        $dao->delete($this->curr);
        $dao->delete($this->version);
        $dao->delete($this->option);
        $dao->commit();
    }

}
