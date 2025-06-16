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

namespace Pasteque\Server\FiscalMirrorAPI;

use \Pasteque\Server\API\API;
use \Pasteque\Server\Model\FiscalTicket;
use \Pasteque\Server\Model\GenericModel;
use \Pasteque\Server\System\DAO\DAOCondition;

class FiscalAPI extends \Pasteque\Server\API\FiscalAPI implements API
{

    private function writeOne($ticket) {
        if ($ticket->getNumber() === 0) {
            /// Special case for EOS, can replace at anytime
            $this->dao->write($ticket);
            $this->dao->commit();
            return;
        }
        // Check if the ticket is already there
        $tktSnap = $this->dao->readSnapshot(static::MODEL_NAME, $ticket->getId());
        if ($tktSnap !== null) {
            if ($tktSnap->getContent() == $ticket->getContent()
                    && $tktSnap->getSignature() == $ticket->getSignature()) {
                // Same ticket, ignore
                return;
            } else {
                throw new \Exception('Trying to override an existing fiscal ticket.');
            }
        }
        $this->dao->write($ticket);
        $this->dao->commit();
    }

    public function import($ticket) {
        $this->writeOne($ticket);
    }

    public function batchImport($tickets) {
        $results = ['successes' => [], 'failures' => []];
        foreach ($tickets as $ticket) {
            try {
                $this->writeOne($ticket);
                $results['successes'][] = $ticket;
            } catch (\Exception $e) {
                $fail = new GenericModel();
                $fail->set('ticket', $ticket);
                $fail->set('reason', $e->getMessage());
                $results['failures'][] = $fail;
            }
        }
        $ret = new GenericModel();
        $ret->set('successes', $results['successes']);
        $ret->set('failures', $results['failures']);
        return $ret;
    }

}

