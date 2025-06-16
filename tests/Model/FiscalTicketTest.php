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

use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\System\DAO\DAOFactory;
use \PHPUnit\Framework\TestCase;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class FiscalTicketTest extends TestCase
{
    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testUpdateSignature() {
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setSequence('00001');
        $fiscalTicket->setNumber(1);
        $fiscalTicket->setDate(new \DateTime());
        $fiscalTicket->setContent('I am a ticket');
        $fiscalTicket->setSignature('I am a poor lonesome signature');
        $exceptionThrown = false;
        try {
            $fiscalTicket->setSignature('I am an evil sigature, niark niark');
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
        $this->assertEquals('I am a poor lonesome signature',
                $fiscalTicket->getSignature());
    }

    public function testUpdateEOSignature() {
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setSequence('00001');
        $fiscalTicket->setNumber(0);
        $fiscalTicket->setDate(new \DateTime());
        $fiscalTicket->setContent('EOS');
        $fiscalTicket->setSignature('I am a poor lonesome signature');
        $fiscalTicket->setSignature('Oh wait!');
        $this->assertTrue(true, 'Did not raised an error');
    }

    public function testSignatureFirst() {
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setSequence('00001');
        $fiscalTicket->setNumber(1);
        $fiscalTicket->setDate(new \DateTime());
        $fiscalTicket->setContent('I am a ticket');
        $fiscalTicket->sign(null);
        $falseTicket = new FiscalTicket();
        $falseTicket->setSequence('00001');
        $falseTicket->setNumber(0);
        $falseTicket->setDate(new \DateTime());
        $falseTicket->setContent('I am a ticket');
        $falseTicket->sign(null);
        $this->assertTrue($fiscalTicket->checkSignature(null));
        $this->assertFalse($fiscalTicket->checkSignature($falseTicket));
    }

    /** @depends testSignatureFirst */
    public function testSignatureChained() {
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setSequence('00001');
        $fiscalTicket->setNumber(1);
        $fiscalTicket->setDate(new \DateTime());
        $fiscalTicket->setContent('I am a ticket');
        $fiscalTicket->sign(null);
        $fiscalTicket2 = new FiscalTicket();
        $fiscalTicket2->setSequence('00001');
        $fiscalTicket2->setNumber(2);
        $fiscalTicket2->setDate(new \DateTime());
        $fiscalTicket2->setContent('I will always be the second.');
        $fiscalTicket2->sign($fiscalTicket);
        $this->assertTrue($fiscalTicket->checkSignature(null));
        $this->assertTrue($fiscalTicket2->checkSignature($fiscalTicket));
        $this->assertFalse($fiscalTicket2->checkSignature(null));
    }

    public function testUntruncatedContent() {
        $pattern = '1234567890';
        $content = $pattern;
        for ($i = 1; $i < 30; $i++) {
            $content .= $pattern;
        }
        $struct = ['type' => FiscalTicket::TYPE_TICKET,  'sequence' => '00001',
                'number' => 1, 'date' => '2021-06-10',
                'content' => $content, 'signature' => 'sig'];
        $fiscalTicket = new FiscalTicket();
        global $dbInfo;
        $dao = DAOFactory::getDAO($dbInfo, ['debug' => true]);
        $fiscalTicket->merge($struct, $dao);
        $dao->close();
        $this->assertEquals($content, $fiscalTicket->getContent());
    }

    /** Retro compatibility test */
    public function testOldBcryptSign() {
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setSequence('00001');
        $fiscalTicket->setNumber(1);
        $fiscalTicket->setDate(new \DateTime());
        $fiscalTicket->setContent('I am a ticket');
        $fiscalTicket->sign(null, 'bcrypt');
        $this->assertTrue(substr($fiscalTicket->getSignature(), 0, 2) == '$2');
        $fiscalTicket2 = new FiscalTicket();
        $fiscalTicket2->setSequence('00001');
        $fiscalTicket2->setNumber(2);
        $fiscalTicket2->setDate(new \DateTime());
        $fiscalTicket2->setContent('I will always be the second.');
        $fiscalTicket2->sign($fiscalTicket, 'bcrypt');
        $this->assertTrue(substr($fiscalTicket2->getSignature(), 0, 2) == '$2');
        $this->assertTrue($fiscalTicket->checkSignature(null));
        $this->assertTrue($fiscalTicket2->checkSignature($fiscalTicket));
        $this->assertFalse($fiscalTicket2->checkSignature(null));
    }

    /** Old bcrypt to default algorithm chaining test */
    public function testOldBcryptTransition() {
        $fiscalTicket = new FiscalTicket();
        $fiscalTicket->setSequence('00001');
        $fiscalTicket->setNumber(1);
        $fiscalTicket->setDate(new \DateTime());
        $fiscalTicket->setContent('I am a ticket');
        $fiscalTicket->sign(null, 'bcrypt');
        $fiscalTicket2 = new FiscalTicket();
        $fiscalTicket2->setSequence('00001');
        $fiscalTicket2->setNumber(2);
        $fiscalTicket2->setDate(new \DateTime());
        $fiscalTicket2->setContent('I will always be the second.');
        $fiscalTicket2->sign($fiscalTicket);
        $this->assertTrue($fiscalTicket->checkSignature(null));
        $this->assertTrue($fiscalTicket2->checkSignature($fiscalTicket));
        $this->assertFalse($fiscalTicket2->checkSignature(null));
    }
}
