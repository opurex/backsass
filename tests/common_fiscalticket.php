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

use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DAOCondition;

function getBaseFiscalTicket($type, $sequence, $number, $date, $content, $signature) {
    return ['type' => $type,
            'sequence' => $sequence,
            'number' => $number,
            'date' => DateUtils::toTimestamp($date),
            'content' => $content,
            'signature' => $signature];
}

function createEos($lastTicket) {
    $eos = new FiscalTicket();
    $eos->setType($lastTicket->getType());
    $eos->setSequence($lastTicket->getSequence());
    $eos->setNumber(0);
    $eos->setDate($lastTicket->getDate());
    $eos->setContent('EOS');
    $eos->sign($lastTicket);
    return $eos;
}

function assertFiscalTicketModelEqModel($m1, $m2, $ut) {
    $ut->assertEquals($m1->getType(), $m2->getType());
    $ut->assertEquals($m1->getSequence(), $m2->getSequence());
    $ut->assertTrue(DateUtils::equals($m1->getDate(), $m2->getDate()));
    $ut->assertEquals($m1->getContent(), $m2->getContent());
    $ut->assertEquals($m1->getSignature(), $m2->getSignature());
}

function assertFiscalTicketModelEqStruct($model, $struct, $ut) {
    $ut->assertEquals($model->getType(), $struct['type']);
    $ut->assertEquals($model->getSequence(), $struct['sequence']);
    $ut->assertTrue(DateUtils::equals($model->getDate(), $struct['date']));
    $ut->assertEquals($model->getContent(), $struct['content']);
    $ut->assertEquals($model->getSignature(), $struct['signature']);
}

function readFiscalTicketSnapshot($type, $sequence, $number, $dao) {
    return $dao->readSnapshot(FiscalTicket::class,
            ['type' => $type, 'sequence' => $sequence, 'number' => $number]);
}
