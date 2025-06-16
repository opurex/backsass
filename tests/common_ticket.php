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

use \Pasteque\Server\Model\Currency;
use \Pasteque\Server\Model\PaymentMode;
use \Pasteque\Server\Model\Ticket;
use \Pasteque\Server\Model\TicketLine;
use \Pasteque\Server\Model\TicketPayment;
use \Pasteque\Server\Model\TicketTax;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOCondition;

function getBaseTicket($cash, $session, $number, $user) {
$date = DateUtils::readString('2018-01-01 10:05');
    $date->add(new \DateInterval('PT' . $number . 'M'));
    return ['cashRegister' => $cash->getId(),
            'sequence' => $session->getSequence(),
            'number' => $number,
            'date' => DateUtils::toTimestamp($date),
            'custCount' => null,
            'price' => 10.0,
            'taxedPrice' => 11.0,
            'discountRate' => 0.0,
            'finalPrice' => 10.0,
            'finalTaxedPrice' => 11.0,
            'custBalance' => 0.0,
            'user' => $user->getId(),
            'lines' => [],
            'taxes' => [],
            'payments' => [],
            'customer' => null,
            'tariffArea' => null,
            'discountProfile' => null];
}

function getBaseLine($tax, $dispOrder, $label, $price) {
    return ['dispOrder' => $dispOrder, 'productLabel' => $label,
            'unitPrice' => null, 'taxedUnitPrice' => $price,
            'quantity' => 1, 'price' => null, 'taxedPrice' => $price,
            'taxRate' => 0.1, 'discountRate' => 0.0,
            'tax' => $tax->getId(),
            'finalPrice' => null, 'finalTaxedPrice' => $price];
}

function getBaseTax($tax, $base, $amount) {
    return ['base' => $base, 'amount' => $amount, 'taxRate' => 0.1,
            'tax' => $tax->getId()];
}

function getBasePayment($paymentMode, $currency, $dispOrder, $amount) {
    return ['dispOrder' => $dispOrder, 'amount' => $amount,
            'currencyAmount' => $amount,
            'paymentMode' => $paymentMode->getId(),
            'currency' => $currency->getId()];
}

function ticketNew($cashRegister, $sequence, $number, $date, $user) {
    $tkt = new Ticket();
    $tkt->setCashRegister($cashRegister);
    $tkt->setSequence($sequence);
    $tkt->setNumber($number);
    $tkt->setDate(DateUtils::readDate($date));
    $tkt->setUser($user);
    return $tkt;
}

function ticketAddLine($tkt, $product, $qty, $discountRate = 0.0) {
    $tktLine = new TicketLine();
    $tktLine->setDispOrder(count($tkt->getLines()) + 1);
    $tktLine->setProduct($product);
    $tktLine->setTax($product->getTax());
    $tktLine->setTaxRate($product->getTax()->getRate());
    $tktLine->setTaxedUnitPrice(round($product->getPriceSell() * (1 + $product->getTax()->getRate()), 2));
    $tktLine->setQuantity($qty);
    $tktLine->setTaxedPrice(round($tktLine->getTaxedUnitPrice() * $qty, 2));
    if ($discountRate != 0.0) {
        $tktLine->setDiscountRate($discountRate);
        $tktLine->setFinalTaxedPrice(round($tktLine->getTaxedPrice() * (1 - $discountRate), 2));
    } else {
        $tktLine->setFinalTaxedPrice($tktLine->getTaxedPrice());
    }
    $tkt->addLine($tktLine);
}

function ticketAddPayment($tkt, $paymentMode, $currency, $amount) {
    $tktPmt = new TicketPayment();
    $tktPmt->setPaymentMode($paymentMode);
    $tktPmt->setCurrency($currency);
    $tktPmt->setAmount($amount);
    $tktPmt->setCurrencyAmount(round($amount * $currency->getRate(), 2));
    $tktPmt->setDispOrder(count($tkt->getPayments()) + 1);
    $tkt->addPayment($tktPmt);
}

function ticketFinalize($tkt) {
    $tktTaxes = [];
    $totalPrice = 0.0;
    $totalBase = 0.0;
    // Add all taxed prices
    foreach ($tkt->getLines() as $line) {
        $tax = $line->getTax();
        if (empty($tktTaxes[$tax->getId()])) {
            $tktTax = new TicketTax();
            $tktTax->setTax($tax);
            $tktTaxes[$tax->getId()] = ['tax' => $tktTax, 'sum' => 0.0];
        }
        $tktTaxes[$tax->getId()]['sum'] = round($tktTaxes[$tax->getId()]['sum'] + $line->getFinalTaxedPrice(), 2);
        $totalPrice = round($totalPrice + $line->getFinalTaxedPrice());
    }
    $tkt->setTaxedPrice($totalPrice);
    // Apply ticket discount
    if ($tkt->getDiscountRate() != 0.0) {
        $totalPrice = round($totalPrice * (1 - $tkt->getDiscountRate()), 2);
        foreach ($tktTaxes as $t) {
            $t['sum'] = round($t['sum'] * (1 - $tkt->getDiscountRate()), 2);
        }
    }
    // Compute final base and amount
    foreach ($tktTaxes as $t) {
        $tktTax = $t['tax'];
        $tktTax->setBase(round($t['sum'] / (1 + $tktTax->getTaxRate()), 2));
        $tktTax->setAmount(round($t['sum'] - $tktTax->getBase(), 2));
        $tkt->addTax($tktTax);
        $totalBase = round($totalBase + $tktTax->getBase(), 2);
    }
    // Set final prices
    $tkt->setFinalPrice($totalBase);
    if ($tkt->getDiscountRate() != 0.0) {
        $tkt->setFinalTaxedPrice(round($totalPrice * (1 - $tkt->getDiscountRate()), 2));
    } else {
        $tkt->setFinalTaxedPrice($totalPrice);
    }
}

/** Check equality of a ticket model and a ticket structure.i
 * Should be used with a model and a dao snapshot to prevent mixing references.
 * @param $ut Unit Test ($this in test classes). */
function assertTicketModelEqModel($m1, $m2, $ut) {
    $ut->assertEquals($m1->getCashRegister()->getId(), $m2->getCashRegister()->getId());
    $ut->assertEquals($m1->getSequence(), $m2->getSequence());
    $ut->assertEquals($m1->getNumber(), $m2->getNumber());
    $ut->assertEquals($m1->getDate()->format('Y-m-d H:i:s'), $m2->getDate()->format('Y-m-d H:i:s'));
    $ut->assertEquals($m1->getUser()->getId(), $m2->getUser()->getId());
    // Check lines
    $ut->assertEquals(count($m1->getLines()), count($m2->getLines()));
    for ($i = 0; $i < count($m1->getLines()); $i++) {
        $m1Line = $m1->getLines()->get($i);
        $m2Line = $m2->getLines()->get($i);
        $ut->assertEquals($m1Line->getDispOrder(), $m2Line->getDispOrder());
        $ut->assertEquals($m1Line->getProductLabel(), $m2Line->getProductLabel());
        $ut->assertEquals($m1Line->getUnitPrice(), $m2Line->getUnitPrice());
        $ut->assertEquals($m1Line->getTaxedUnitPrice(), $m2Line->getTaxedUnitPrice());
        $ut->assertEquals($m1Line->getQuantity(), $m2Line->getQuantity());
        $ut->assertEquals($m1Line->getPrice(), $m2Line->getPrice());
        $ut->assertEquals($m1Line->getTaxedPrice(), $m2Line->getTaxedPrice());
        $ut->assertEquals($m1Line->getTaxRate(), $m2Line->getTaxRate());
        $ut->assertEquals($m1Line->getDiscountRate(), $m2Line->getDiscountRate());
        $ut->assertEquals($m1Line->getFinalPrice(), $m2Line->getFinalPrice());
        $ut->assertEquals($m1Line->getFinalTaxedPrice(), $m2Line->getFinalTaxedPrice());
        $prd1 = $m1Line->getProduct() !== null ? $m1Line->getProduct()->getId() : null;
        $prd2 = $m2Line->getProduct() !== null ? $m2Line->getProduct()->getId() : null;
        $ut->assertEquals($prd1, $prd2);
        $tax1 = $m1Line->getTax() !== null ? $m1Line->getTax()->getId() : null;
        $tax2 = $m2Line->getTax() !== null ? $m2Line->getTax()->getId() : null;
        $ut->assertEquals($tax1, $tax2);
    }
    // Check tax lines
    $ut->assertEquals(count($m1->getTaxes()), count($m2->getTaxes()));
    for ($i = 0; $i < count($m1->getTaxes()); $i++) {
        $m1Tax = $m1->getTaxes()->get($i);
        $m2Tax = $m2->getTaxes()->get($i);
        $ut->assertEquals($m1Tax->getTax()->getId(), $m2Tax->getTax()->getId());
        $ut->assertEquals($m1Tax->getTaxRate(), $m2Tax->getTaxRate());
        $ut->assertEquals($m1Tax->getBase(), $m2Tax->getBase());
        $ut->assertEquals($m1Tax->getAmount(), $m2Tax->getAmount());
    }
    // Check payments
    $ut->assertEquals(count($m1->getPayments()), count($m2->getPayments()));
    for ($i = 0; $i < count($m2->getPayments()); $i++) {
        $m1Pay = $m1->getPayments()->get($i);
        $m2Pay = $m2->getPayments()->get($i);
        $ut->assertEquals($m1Pay->getPaymentMode()->getId(), $m2Pay->getPaymentMode()->getId());
        $ut->assertEquals($m1Pay->getCurrency()->getId(), $m2Pay->getCurrency()->getId());
        $ut->assertEquals($m1Pay->getAmount(), $m2Pay->getAmount());
        $ut->assertEquals($m1Pay->getCurrencyAmount(), $m2Pay->getCurrencyAmount());
    }
}

/** Check equality of a ticket model and a ticket structure.
 * @param $ut Unit Test ($this in test classes). */
function assertTicketModelEqStruct($model, $struct, $ut) {
    // Check direct fields
    $ut->assertEquals($model->getCashRegister()->getId(), $struct['cashRegister']);
    $ut->assertEquals($model->getSequence(), $struct['sequence']);
    $ut->assertEquals($model->getNumber(), $struct['number']);
    $ut->assertEquals($model->getDate()->format('Y-m-d H:i:s'), DateUtils::readDate($struct['date'])->format('Y-m-d H:i:s'));
    $ut->assertEquals($model->getUser()->getId(), $struct['user']);
    // Check lines
    $ut->assertEquals(count($model->getLines()), count($struct['lines']));
    for ($i = 0; $i < count($struct['lines']); $i++) {
        $mLine = $model->getLines()->get($i);
        $sLine = $struct['lines'][$i];
        $ut->assertEquals($mLine->getDispOrder(), $sLine['dispOrder']);
        $ut->assertEquals($mLine->getProductLabel(), $sLine['productLabel']);
        $ut->assertEquals($mLine->getUnitPrice(), $sLine['unitPrice']);
        $ut->assertEquals($mLine->getTaxedUnitPrice(), $sLine['taxedUnitPrice']);
        $ut->assertEquals($mLine->getQuantity(), $sLine['quantity']);
        $ut->assertEquals($mLine->getPrice(), $sLine['price']);
        $ut->assertEquals($mLine->getTaxedPrice(), $sLine['taxedPrice']);
        $ut->assertEquals($mLine->getTaxRate(), $sLine['taxRate']);
        $ut->assertEquals($mLine->getDiscountRate(), $sLine['discountRate']);
        $ut->assertEquals($mLine->getFinalPrice(), $sLine['finalPrice']);
        $ut->assertEquals($mLine->getFinalTaxedPrice(), $sLine['finalTaxedPrice']);
        $prd = $mLine->getProduct() !== null ? $mLine->getProduct()->getId() : null;
        $ut->assertEquals($prd, (isset($sLine['product']) ? $sLine['product'] : null));
        $tax = $mLine->getTax() !== null ? $mLine->getTax()->getId() : null;
        $ut->assertEquals($tax, (isset($sLine['tax']) ? $sLine['tax'] : null));
    }
    // Check tax lines
    $ut->assertEquals(count($model->getTaxes()), count($struct['taxes']));
    for ($i = 0; $i < count($struct['taxes']); $i++) {
        $mTax = $model->getTaxes()->get($i);
        $sTax = $struct['taxes'][$i];
        $ut->assertEquals($mTax->getTax()->getId(), $sTax['tax']);
        $ut->assertEquals($mTax->getTaxRate(), $sTax['taxRate']);
        $ut->assertEquals($mTax->getBase(), $sTax['base']);
        $ut->assertEquals($mTax->getAmount(), $sTax['amount']);
    }
    // Check payments
    $ut->assertEquals(count($model->getPayments()), count($struct['payments']));
    for ($i = 0; $i < count($struct['payments']); $i++) {
        $mPay = $model->getPayments()->get($i);
        $sPay = $struct['payments'][$i];
        $ut->assertEquals($mPay->getPaymentMode()->getId(), $sPay['paymentMode']);
        $ut->assertEquals($mPay->getCurrency()->getId(), $sPay['currency']);
        $ut->assertEquals($mPay->getAmount(), $sPay['amount']);
        $ut->assertEquals($mPay->getCurrencyAmount(), $sPay['currencyAmount']);
    }
}

function readTicketSnapshot($cashRegister, $sequence, $number, $dao) {
    $search = $dao->search(Ticket::class,
            [new DAOCondition('cashRegister', '=', $cashRegister),
             new DAOCondition('sequence', '=', $sequence),
             new DAOCondition('number', '=', $number)]);
    if (count($search) > 0) {
	return $dao->readSnapshot(Ticket::class, $search[0]->getId());
    }
    return null;
}
