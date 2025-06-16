<?php
//    Pasteque API
//
//    Copyright (C) 2012-2017 Pasteque contributors
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

namespace Pasteque\Server\API;

use \Pasteque\Server\CommonAPI\OptionAPI;
use \Pasteque\Server\Exception\RecordNotFoundException;
use \Pasteque\Server\Model\GenericModel;
use \Pasteque\Server\Model\Option;

class SyncAPI implements API {

    protected $cashRegisterAPI;
    protected $cashSessionAPI;
    protected $paymentModeAPI;
    protected $currencyAPI;
    protected $placeAPI;
    protected $userAPI;
    protected $roleAPI;
    protected $taxAPI;
    protected $categoryAPI;
    protected $productAPI;
    protected $tariffAreaAPI;
    protected $discountAPI;
    protected $customerAPI;
    protected $discountProfileAPI;
    protected $resourceAPI;
    protected $optionAPI;
    protected $defaultBackOfficeUrl;

    public function __construct($app) {
        $this->cashRegisterAPI = CashregisterAPI::fromApp($app);
        $this->cashSessionAPI = CashsessionAPI::fromApp($app);
        $this->paymentModeAPI = PaymentmodeAPI::fromApp($app);
        $this->currencyAPI = CurrencyAPI::fromApp($app);
        $this->placeAPI = PlaceAPI::fromApp($app);
        $this->userAPI = UserAPI::fromApp($app);
        $this->roleAPI = RoleAPI::fromApp($app);
        $this->taxAPI = TaxAPI::fromApp($app);
        $this->categoryAPI = CategoryAPI::fromApp($app);
        $this->productAPI = ProductAPI::fromApp($app);
        $this->tariffAreaAPI = TariffareaAPI::fromApp($app);
        $this->discountAPI = DiscountAPI::fromApp($app);
        $this->customerAPI = CustomerAPI::fromApp($app);
        $this->discountProfileAPI = DiscountprofileAPI::fromApp($app);
        $this->resourceAPI = ResourceAPI::fromApp($app);
        $this->optionAPI = OptionAPI::fromApp($app);
        $this->defaultBackOfficeUrl = $app->getDefaultBackOfficeUrl();
    }

    public static function fromApp($app) {
        return new static($app);
    }

    private function getOptions() {
        $options = $this->optionAPI->getAll();
        // Add the default backoffice.url option from api configuration
        // when not explicitely set from the user's data.
        $boFound = false;
        foreach ($options as $opt) {
            if ($opt->getName() == 'backoffice.url') {
                $boFound = true;
                break;
            }
        }
        if (!$boFound && !empty($this->defaultBackOfficeUrl)) {
            $opt = new Option();
            $opt->setName('backoffice.url');
            $opt->setContent($this->defaultBackOfficeUrl);
            $options[] = $opt;
        }
        return $options;
    }

    /** Get all the data, including invisible/disabled entries. */
    public function sync() {
        $result = new GenericModel();
        // Send everything
        $result->set('cashRegisters', $this->cashRegisterAPI->getAll());
        $result->set('paymentmodes', $this->paymentModeAPI->getAll());
        $result->set('currencies', $this->currencyAPI->getAll());
        $result->set('floors', $this->placeAPI->getAll());
        $result->set('users', $this->userAPI->getAll());
        $result->set('roles', $this->roleAPI->getAll());
        $result->set('taxes', $this->taxAPI->getAll());
        $result->set('categories', $this->categoryAPI->getAll());
        $result->set('products', $this->productAPI->getAll());
        $result->set('tariffareas', $this->tariffAreaAPI->getAll());
        $result->set('discounts', $this->discountAPI->getAll());
        $result->set('customers', $this->customerAPI->getAll());
        $result->set('discountprofiles', $this->discountProfileAPI->getAll());
        $result->set('resources', $this->resourceAPI->getAll());
        $result->set('options', $this->getOptions());
        return $result;
    }

    /**
     * Get all data for a cashregister to operate. This doesn't includes
     * invisible/disabled entries.
     * @throws RecordNotFoundException When no cash register was found.
     */
    public function syncCashRegister($cashRegisterName) {
        $result = new GenericModel();
        // Look for the cash register, reject if not found.
        $cashRegister = $this->cashRegisterAPI->getByName($cashRegisterName);
        if ($cashRegister === null) {
            throw new RecordNotFoundException(
                    \Pasteque\Server\Model\CashRegister::class,
                    ['label' => $cashRegisterName]);
        }
        // Send everything
        $result->set('cashRegister', $cashRegister);
        $result->set('cash', $this->cashSessionAPI->get($cashRegister->getId()));
        $result->set('paymentmodes', $this->paymentModeAPI->getAll());
        $result->set('currencies', $this->currencyAPI->getAll());
        $result->set('floors', $this->placeAPI->getAll());
        $result->set('users', $this->userAPI->getAll());
        $result->set('roles', $this->roleAPI->getAll());
        $result->set('taxes', $this->taxAPI->getAll());
        $result->set('categories', $this->categoryAPI->getAll());
        $result->set('products', $this->productAPI->getAllVisible());
        $result->set('tariffareas', $this->tariffAreaAPI->getAll());
        $result->set('discounts', $this->discountAPI->getAll());
        $result->set('customers', $this->customerAPI->getAll());
        // Set top customers as id only not to duplicate data
        $topCust = $this->customerAPI->getTopIds();
        $result->set('topcustomers', $topCust);
        $result->set('discountprofiles', $this->discountProfileAPI->getAll());
        $result->set('resources', $this->resourceAPI->getAll());
        $result->set('options', $this->getOptions());
        return $result;
    }

}
