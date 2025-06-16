<?php
//    Pastèque API
//
//    Copyright (C) 2012-2015 Scil (http://scil.coop)
//    Cédric Houbart, Philippe Pary
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

namespace Pasteque\Server\Model;

use \Pasteque\Server\Model\Field\BoolField;
use \Pasteque\Server\Model\Field\DateField;

/**
 * A request to create an archive. Archives are created in background to
 * avoid http server execution limits. See ArchiveAPI to create requests
 * and processing them.
 * The date interval cannot be more than one year (constraint from the LF2016).
 * @Entity
 * @Table(name="archiverequests")
 */
class ArchiveRequest
{
    protected static function getDirectFieldNames() {
        return [
                new DateField('dateStart'),
                new DateField('dateStop'),
                new BoolField('processing')];
    }
    protected static function getAssociationFieds() {
        return [];
    }

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Start date of the archive, relative to the registration date of the
     * fiscal tickets.
     * @Column(type="datetime")
     */
    protected $startDate;
    public function getStartDate() { return $this->startDate; }
    public function setStartDate($startDate) {
        $this->startDate = $startDate;
    }

    /**
     * End date of the archive, relative to the registration date of the
     * fiscal tickets.
     * @Column(type="datetime")
     */
    protected $stopDate;
    public function getStopDate() { return $this->stopDate; }
    public function setStopDate($stopDate) {
        $this->stopDate = $stopDate;
    }

    /**
     * Flag to set once the archive is being processed, to prevent multiple
     * creations for long operations.
     * @Column(type="boolean")
     */
    protected $processing = false;
    public function getProcessing() { return $this->processing; }
    public function isProcessing() { return $this->getProcessing(); }
    public function setProcessing($processing) {
        $this->processing = $processing;
    }
}
