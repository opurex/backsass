<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
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

// Some utility functions to test tickets.

use \Pasteque\Server\Model\Session;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOCondition;

/** Check equality of a ticket model and a ticket structure.i
 * Should be used with a model and a dao snapshot to prevent mixing references.
 * @param $ut Unit Test ($this in test classes). */
function assertSessionModelEqModel($m1, $m2, $ut) {
    $ut->assertEquals($m1->getCashRegister()->getId(), $m2->getCashRegister()->getId());
    $ut->assertEquals($m1->getSequence(), $m2->getSequence());
    $ut->assertTrue(DateUtils::equals($m1->getOpenDate(), $m2->getOpenDate()));
    $ut->assertTrue(DateUtils::equals($m1->getCloseDate(), $m2->getCloseDate()));
    $ut->assertEquals($m1->getOpenCash(), $m2->getOpenCash());
    $ut->assertEquals($m1->getCloseCash(), $m2->getCloseCash());
    $ut->assertEquals($m1->getExpectedCash(), $m2->getExpectedCash());
    $ut->assertEquals($m1->getTicketCount(), $m2->getTicketCount());
    $ut->assertEquals($m1->getCustCount(), $m2->getCustCount());
    $ut->assertEquals($m1->getCs(), $m2->getCs());
    $ut->assertEquals($m1->getCsPeriod(), $m2->getCsPeriod());
    $ut->assertEquals($m1->getCsFYear(), $m2->getCsFYear());
    $ut->assertEquals($m1->getCsPerpetual(), $m2->getCsPerpetual());
    // Check payments
    $ut->assertEquals(count($m1->getPayments()), count($m2->getPayments()));
    for ($i = 0; $i < count($m1->getPayments()); $i++) {
        $m1Pmt = $m1->getPayments()->get($i);
        $m2Pmt = $m2->getPayments()->get($i);
        $ut->assertEquals($m1Pmt->getAmount(), $m2Pmt->getAmount());
        $ut->assertEquals($m1Pmt->getCurrencyAmount(), $m2Pmt->getCurrencyAmount());
        $ut->assertEquals($m1Pmt->getPaymentMode()->getId(), $m2Pmt->getPaymentMode()->getId());
        $ut->assertEquals($m1Pmt->getCurrency()->getId(), $m2Pmt->getCurrency()->getId());
    }
    // Check taxes
    $ut->assertEquals(count($m1->getTaxes()), count($m2->getTaxes()));
    for ($i = 0; $i < count($m1->getTaxes()); $i++) {
        $m1Tax = $m1->getTaxes()->get($i);
        $m2Tax = $m2->getTaxes()->get($i);
        $ut->assertEquals($m1Tax->getTaxRate(), $m2Tax->getTaxRate());
        $ut->assertEquals($m1Tax->getBase(), $m2Tax->getBase());
        $ut->assertEquals($m1Tax->getBasePeriod(), $m2Tax->getBasePeriod());
        $ut->assertEquals($m1Tax->getBaseFYear(), $m2Tax->getBaseFYear());
        $ut->assertEquals($m1Tax->getAmount(), $m2Tax->getAmount());
        $ut->assertEquals($m1Tax->getAmountPeriod(), $m2Tax->getAmountPeriod());
        $ut->assertEquals($m1Tax->getAmountFYear(), $m2Tax->getAmountFYear());
        $ut->assertEquals($m1Tax->getTax()->getId(), $m2Tax->getTax()->getId());
    }
    // Check category sales
    $ut->assertEquals(count($m1->getCatSales()), count($m2->getCatSales()));
    for ($i = 0; $i < count($m2->getCatSales()); $i++) {
        $m1Cat = $m1->getCatSales()->get($i);
        $m2Cat = $m2->getCatSales()->get($i);
        $ut->assertEquals($m1Cat->getReference(), $m2Cat->getReference());
        $ut->assertEquals($m1Cat->getLabel(), $m2Cat->getLabel());
        $ut->assertEquals($m1Cat->getAmount(), $m2Cat->getAmount());
    }
    // Check category taxes
    $ut->assertEquals(count($m1->getCatTaxes()), count($m2->getCatTaxes()));
    for ($i = 0; $i < count($m1->getCatTaxes()); $i++) {
        $m1CatTax = $m1->getCatTaxes()->get($i);
        $m2CatTax = $m2->getCatTaxes()->get($i);
        $ut->assertEquals($m1CatTax->getReference(), $m2CatTax->getReference());
        $ut->assertEquals($m1CatTax->getLabel(), $m2CatTax->getLabel());
        $ut->assertEquals($m1CatTax->getBase(), $m2CatTax->getBase());
        $ut->assertEquals($m1CatTax->getAmount(), $m2CatTax->getAmount());
        $ut->assertEquals($m1CatTax->getTax()->getId(), $m2CatTax->getTax()->getId());
    }
    // Check cust balances
    $ut->assertEquals(count($m1->getCustBalances()), count($m2->getCustBalances()));
    for ($i = 0; $i < count($m1->getCustBalances()); $i++) {
        $m1CB = $m1->getCustBalances()->get($i);
        $m2CB = $m2->getCustBalances()->get($i);
        $ut->assertEquals($m1CB->getBalance(), $m2CB->getBalance());
        $ut->assertEquals($m1CB->getCustomer()->getId(), $m2CB->getCustomer()->getId());
    }
}

/** Check equality of a ticket model and a ticket structure.
 * @param $ut Unit Test ($this in test classes). */
function assertSessionModelEqStruct($model, $struct, $ut) {
    $ut->assertEquals($model->getCashRegister()->getId(), $struct['cashRegister']);
    $ut->assertEquals($model->getSequence(), $struct['sequence']);
    $ut->assertTrue(DateUtils::equals($model->getOpenDate(), $struct['openDate']));
    $ut->assertTrue(DateUtils::equals($model->getCloseDate(), $struct['closeDate']));
    $ut->assertEquals($model->getOpenCash(), $struct['openCash']);
    $ut->assertEquals($model->getCloseCash(), $struct['closeCash']);
    $ut->assertEquals($model->getExpectedCash(), $struct['expectedCash']);
    $ut->assertEquals($model->getTicketCount(), $struct['ticketCount']);
    $ut->assertEquals($model->getCustCount(), $struct['custCount']);
    $ut->assertEquals($model->getCs(), $struct['cs']);
    $ut->assertEquals($model->getCsPeriod(), $struct['csPeriod']);
    $ut->assertEquals($model->getCsFYear(), $struct['csFYear']);
    $ut->assertEquals($model->getCsPerpetual(), $struct['csPerpetual']);
    // Check payments
    $ut->assertEquals(count($model->getPayments()), count($struct['payments']));
    for ($i = 0; $i < count($struct['payments']); $i++) {
        $m1Pmt = $model->getPayments()->get($i);
        $m2Pmt = $struct['payments'][$i];
        $ut->assertEquals($m1Pmt->getAmount(), $m2Pmt['amount']);
        $ut->assertEquals($m1Pmt->getCurrencyAmount(), $m2Pmt['currencyAmount']);
        $ut->assertEquals($m1Pmt->getPaymentMode()->getId(), $m2Pmt['paymentMode']);
        $ut->assertEquals($m1Pmt->getCurrency()->getId(), $m2Pmt['currency']);
    }
    // Check taxes
    $ut->assertEquals(count($model->getTaxes()), count($struct['taxes']));
    for ($i = 0; $i < count($struct['taxes']); $i++) {
        $m1Tax = $model->getTaxes()->get($i);
        $m2Tax = $struct['taxes'][$i];
        $ut->assertEquals($m1Tax->getTaxRate(), $m2Tax['taxRate']);
        $ut->assertEquals($m1Tax->getBase(), $m2Tax['base']);
        $ut->assertEquals($m1Tax->getBasePeriod(), $m2Tax['basePeriod']);
        $ut->assertEquals($m1Tax->getBaseFYear(), $m2Tax['baseFYear']);
        $ut->assertEquals($m1Tax->getAmount(), $m2Tax['amount']);
        $ut->assertEquals($m1Tax->getAmountPeriod(), $m2Tax['amountPeriod']);
        $ut->assertEquals($m1Tax->getAmountFYear(), $m2Tax['amountFYear']);
        $ut->assertEquals($m1Tax->getTax()->getId(), $m2Tax['tax']);
    }
    // Check category sales
    $ut->assertEquals(count($model->getCatSales()), count($struct['catSales']));
    for ($i = 0; $i < count($struct['catSales']); $i++) {
        $m1Cat = $model->getCatSales()->get($i);
        $m2Cat = $struct['catSales'][$i];
        $ut->assertEquals($m1Cat->getReference(), $m2Cat['reference']);
        $ut->assertEquals($m1Cat->getLabel(), $m2Cat['label']);
        $ut->assertEquals($m1Cat->getAmount(), $m2Cat['amount']);
    }
    // Check category taxes
    $ut->assertEquals(count($model->getCatTaxes()), count($struct['catTaxes']));
    for ($i = 0; $i < count($struct['catTaxes']); $i++) {
        $m1CatTax = $model->getCatTaxes()->get($i);
        $m2CatTax = $struct['catTaxes'][$i];
        $ut->assertEquals($m1CatTax->getReference(), $m2CatTax['reference']);
        $ut->assertEquals($m1CatTax->getLabel(), $m2CatTax['label']);
        $ut->assertEquals($m1CatTax->getBase(), $m2CatTax['base']);
        $ut->assertEquals($m1CatTax->getAmount(), $m2CatTax['amount']);
        $ut->assertEquals($m1CatTax->getTax()->getId(), $m2CatTax['tax']);
    }
    // Check cust balances
    $ut->assertEquals(count($model->getCustBalances()), count($struct['custBalances']));
    for ($i = 0; $i < count($model->getCustBalances()); $i++) {
        $m1CB = $model->getCustBalances()->get($i);
        $m2CB = $struct['custBalances'][$i];
        $ut->assertEquals($m1CB->getBalance(), $m2CB['balance']);
        $ut->assertEquals($m1CB->getCustomer()->getId(), $m2CB['customer']);
    }
}

function readSessionSnapshot($cashRegister, $sequence, $dao) {
    $search = $dao->search(CashSession::class,
            [new DAOCondition('cashRegister', '=', $cashRegister),
             new DAOCondition('sequence', '=', $sequence)]);
    if (count($search) > 0) {
	return $dao->readSnapshot(CashSession::class, $search[0]->getId());
    }
    return null;
}
