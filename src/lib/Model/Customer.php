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
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DateUtils;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Customer
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="customers")
 */
class Customer extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return ['id',
                new StringField('dispName'),
                new StringField('card'),
                new FloatField('maxDebt'),
                new FloatField('balance'),
                new StringField('firstName'),
                new StringField('lastName'),
                new StringField('email'),
                new StringField('phone1'),
                new StringField('phone2'),
                new StringField('fax'),
                new StringField('addr1'),
                new StringField('addr2'),
                new StringField('zipCode'),
                new StringField('city'),
                new StringField('region'),
                new StringField('country'),
                new StringField('note', ['length' => null]),
                new BoolField('visible'),
                'hasImage',
                new DateField('expireDate', ['nullable' => true])];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'discountProfile',
                 'class' => '\Pasteque\Server\Model\DiscountProfile',
                 'null' => true
                 ],
                [
                 'name' => 'tariffArea',
                 'class' => '\Pasteque\Server\Model\TariffArea',
                 'null' => true
                 ],
                [
                 'name' => 'tax',
                 'class' => '\Pasteque\Server\Model\Tax',
                 'null' => true
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return 'id';
    }
    /** Customers don't have reference yet. This is the same as getId(). */
    public function getReference() {
        return $this->getId();
    }

    /** Minimal size of card number */
    const CARD_SIZE = 7;
    /** Barcode prefix for customer cards */
    const CARD_PREFIX = "c";

    /**
     * Id of the Customer.
     * This is also the old 'number' field that was mapped to 'TAXID'
     * @var string
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Name of the Customer
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $dispName;
    public function getDispName() { return $this->dispName; }
    public function setDispName($dispName) { $this->dispName = $dispName; }

    /**
     * Customer card, without padding nor card prefix.
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $card = '';
    public function getCard() { return $this->card; }
    public function setCard($card) { $this->card = $card; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $firstName = '';
    public function getFirstName() { return $this->firstName; }
    public function setFirstName($firstName) { $this->firstName = $firstName; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $lastName = '';
    public function getLastName() { return $this->lastName; }
    public function setLastName($lastName) { $this->lastName = $lastName; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $email = '';
    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $phone1 = '';
    public function getPhone1() { return $this->phone1; }
    public function setPhone1($phone1) { $this->phone1 = $phone1; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $phone2 = '';
    public function getPhone2() { return $this->phone2; }
    public function setPhone2($phone2) { $this->phone2 = $phone2; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $fax = '';
    public function getFax() { return $this->fax; }
    public function setFax($fax) { $this->fax = $fax; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $addr1 = '';
    public function getAddr1() { return $this->addr1; }
    public function setAddr1($addr1) { $this->addr1 = $addr1; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $addr2 = '';
    public function getAddr2() { return $this->addr2; }
    public function setAddr2($addr2) { $this->addr2 = $addr2; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $zipCode = '';
    public function getZipCode() { return $this->zipCode; }
    public function setZipCode($zipCode) { $this->zipCode = $zipCode; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $city = '';
    public function getCity() { return $this->city; }
    public function setCity($city) { $this->city = $city; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $region = '';
    public function getRegion() { return $this->region; }
    public function setRegion($region) { $this->region = $region; }

    /**
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $country = '';
    public function getCountry() { return $this->country; }
    public function setCountry($country) { $this->country = $country; }

    /**
     * Id of the Tax of the Customer that override the taxes of the products.
     * @var int|null
     * @SWG\Property()
     * @ManyToOne(targetEntity="Tax")
     * @JoinColumn(name="tax_id", referencedColumnName="id", nullable=true)
     */
    protected $tax;
    public function getTax() { return $this->tax; }
    public function setTax($tax) { $this->tax = $tax; }


    /**
     * Id of the Discount Profile of the Customer
     * @var int|null
     * @SWG\Property(format="int32")
     * @ManyToOne(targetEntity="DiscountProfile")
     * @JoinColumn(name="discountprofile_id", referencedColumnName="id", nullable=true)
     */
    protected $discountProfile;
    public function getDiscountProfile() { return $this->discountProfile; }
    public function setDiscountProfile($profile) {
        $this->discountProfile = $profile;
    }

    /**
     * Id of the Tariff Area of the Customer
     * @var int|null
     * @SWG\Property()
     * @ManyToOne(targetEntity="TariffArea")
     * @JoinColumn(name="tariffarea_id", referencedColumnName="id", nullable=true)
     */
    protected $tariffArea;
    public function getTariffArea() { return $this->tariffArea; }
    public function setTariffArea($area) {
        $this->tariffArea = $area;
    }

    /**
     * Debt/prepaid amount of the Customer. When negative, the customer is
     * in debt. When positive, the customer has prepaid. Balance may be out
     * of bounds because transactions are checked on client side and if it
     * passed, the transaction was validated and must be acounted.
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float")
     */
    protected $balance = 0.0;
    public function getBalance() { return round($this->balance, 5); }
    public function setBalance($balance) {
        $this->balance = round($balance, 5);
    }
    public function addBalance($amount) {
        $this->balance = round($this->getBalance() + round($amount, 5), 5);
    }
    public function addPrepaid($amount) {
        $this->addBalance($amount);
    }
    public function removePrepaid($amount) {
        $this->addBalance(-1*$amount);
    }
    public function addDebt($amount) {
        $this->addBalance(-1*$amount);
    }
    public function recoverDebt($amount) {
        $this->addBalance($amount);
    }

    /**
     * MaxDebt Amount of the Customer. It is positive, the balance should
     * not go beyond -maxDebt.
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float")
     */
    protected $maxDebt = 0.0;
    public function getMaxDebt() { return round($this->maxDebt, 5); }
    public function setMaxDebt($max) { $this->maxDebt = round($max, 5); }

    /**
     * Free private note.
     * @var string|null
     * @SWG\Property()
     * @Column(type="text")
     */
    protected $note = '';
    public function getNote() { return $this->note; }
    public function setNote($note) { $this->note = $note; }

    /**
     * Is the Customer Visible?
     * @var int
     * @SWG\Property(format="int32")
     * @Column(type="boolean")
     */
    protected $visible = true;
    public function getVisible() { return $this->visible; }
    /** Alias for getVisible (the latter is required for Doctrine) */
    public function isVisible() { return $this->getVisible(); }
    public function setVisible($visible) { $this->visible = $visible; }

    /**
     * @var string|null
     * @SWG\Property(format="date-time")
     * @Column(type="datetime", nullable=true)
     */
    protected $expireDate;
    public function getExpireDate() { return $this->expireDate; }
    public function setExpireDate($expireDate) { $this->expireDate = $expireDate; }

    /**
     * True if an image can be found for this model.
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $hasImage = false;
    public function getHasImage() { return $this->hasImage; }
    public function hasImage() { return $this->getHasImage(); }
    public function setHasImage($hasImage) { $this->hasImage = $hasImage; }

    /** Add some old deprecated virtual fields */
    public function toStruct() {
        $struct = parent::toStruct();
        $struct['number'] = $this->getId();
        $struct['key'] = sprintf('%d-%s', $this->getId(), $this->getDispName());
        $struct['expireDate'] = DateUtils::toTimestamp($this->getExpireDate());
        return $struct;
    }

}
