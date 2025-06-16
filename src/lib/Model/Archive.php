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

use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;

/**
 * A fiscal ticket archive. Archives are created from ArchiveRequests through
 * ArchiveAPI.
 * @Entity
 * @Table(name="archives")
 */
class Archive
{
    protected static function getDirectFieldNames() {
        return [new IntField('number'),
                new StringField('info', ['length' => null]),
                new StringField('content_hash'), 'content',
                new StringField('signature')];
    }
    protected static function getAssociationFieds() {
        return [];
    }

    /**
     * @Id @Column(type="integer")
     */
    protected $number;
    public function getId() { return $this->number; }
    public function getNumber() { return $this->number; }
    public function setNumber($number) {
        $this->number = $number;
    } 

    /**
     * Informations about the archive. It is also stored into the content
     * and gives various informations in json format.
     * @Column(type="text")
     */
    protected $info;
    public function getInfo() { return $this->info; }
    public function setInfo($info) { $this->info = $info; }

    /**
     * Archive data.
     * @Column(type="blob")
     */
    protected $content;
    public function getContent() {
        if (gettype($this->content) == 'resource') {
            // Doctrine returns a stream when reading from database.
            return stream_get_contents($this->content);
        } else {
            // When using setContent it has it's actual value.
            return $this->content;
        }
    }
    public function setContent($content) {
        $this->content = $content;
        $this->contentHash = password_hash($content, PASSWORD_DEFAULT);
    }

    /**
     * Archive content hash. It is used to sign and chain the record
     * instead of the archive content.
     * @Column(type="string")
     */
    protected $contentHash;
    public function getContentHash() { return $this->contentHash; }
    /**
     * Required by Doctrine to load from the database. The hash cannot be
     * modified and is computed in setContent.
     */
    public function setContentHash($hash) { // Required by Doctrine
        if ($this->contentHash !== null) {
            return;
        }
        $this->contentHash = $hash;
        if ($this->signature !== null && $this->number !== 0) {
            throw new \InvalidArgumentException('Changing the signature is not allowed.');
        }
        $this->signature = $signature;
    }


    /**
     * Archive signature. The signature is computed from archive n and n-1.
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $signature;
    public function getSignature() { return $this->signature; }
    public function setSignature($signature) {
        if ($this->signature !== null && $this->number !== 0) {
            throw new \InvalidArgumentException('Changing the signature is not allowed.');
        }
        $this->signature = $signature;
    }

    protected function getHashBase() {
        return $this->getNumber() . '-' . $this->getInfo() . '-'
                . $this->contentHash;
    }

    /** Set the signature of the ticket. */
    public function sign($prevArchive) {
        $data = null;
        if ($prevArchive === null) {
            $data = $this->getHashBase();
        } else {
            $data = $prevArchive->getSignature() . '-' . $this->getHashBase();
        }
        $signature = password_hash($data, PASSWORD_DEFAULT);
        if ($signature === false) {
            // Error
        }
        $this->setSignature($signature);
    }

    /** Check the signature of the ticket. */
    public function checkSignature($prevArchive) {
        $data = null;
        if ($prevArchive === null) {
            $data = $this->getHashBase();
        } else {
            $data = $prevArchive->getSignature() . '-' . $this->getHashBase();
        }
        return password_verify($data, $this->getSignature());
    }
}
