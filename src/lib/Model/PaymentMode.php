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
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class PaymentMode
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="paymentmodes")
 */
class PaymentMode extends DoctrineMainModel
{
    public function __toString() { return $this->getReference(); }

    const TYPE_DEFAULT = 0;
    /** Requires a customer */
    const CUST_ASSIGNED = 1;
    /** Uses customer's debt (includes CUST_ASSIGNED) */
    const CUST_DEBT = 3; // 2 + PaymentMode::CUST_ASSIGNED
    /** Uses customer's prepaid (includes CUST_ASSIGNED) */
    const CUST_PREPAID = 5; // 4 + PaymentMode::CUST_ASSIGNED;

    protected static function getDirectFieldNames() {
        return [
                new StringField('reference'),
                new StringField('label'),
                new StringField('backLabel'),
                new IntField('type'),
                new BoolField('visible'),
                new IntField('dispOrder'),
                'hasImage'
                ];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'values',
                 'class' => '\Pasteque\Server\Model\PaymentModeValue',
                 'array' => true,
                 'embedded' => true
                 ],
                [
                 'name' => 'returns',
                 'class' => '\Pasteque\Server\Model\PaymentModeReturn',
                 'array' => true,
                 'embedded' => true
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return 'reference';
    }

    public function __construct() {
        $this->values = new \Doctrine\Common\Collections\ArrayCollection();
        $this->returns = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Internal ID of the payment mode for performance issues.
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Code of the payment mode, user-friendly ID.
     * This was previously 'code'. Is is passed to toStruct and merge
     * for compatibility.
     * @var string
     * @SWG\Property()
     * @Column(type="string", unique=true)
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($ref) { $this->reference = $ref; }

    /**
     * Label of the payment mode
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    /**
     * Label of the payment mode when used for returning.
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $backLabel = '';
    public function getBackLabel() { return $this->backLabel; }
    public function setBackLabel($backLabel) { $this->backLabel = $backLabel; }

    /**
     * Type of the payment mode (see constants).
     * @var int Type
     * @SWG\Property(format="int32")
     * @Column(type="integer")
     */
    protected $type = self::TYPE_DEFAULT;
    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; }

    public function usesDebt() {
        return ($this->type & static::CUST_DEBT) == static::CUST_DEBT;
    }
    public function usesPrepay() {
        return ($this->type & static::CUST_PREPAID) == static::CUST_PREPAID;
    }

    /**
     * @OneToMany(targetEntity="\Pasteque\Server\Model\PaymentModeValue", mappedBy="paymentMode", cascade={"all"})
     * @OrderBy({"value" = "DESC"})
     */
    // Warning: no orphan removal as it doesn't work with Mysql. This is handled in PaymentModeAPI.
    protected $values;
    public function getValues() { return $this->values; }
    public function setValues($values) {
        $this->clearValues();
        foreach ($values as $value) {
            $this->addValue($value);
        }
    }
    public function addValue($value) {
        $this->values->add($value);
        $value->setPaymentMode($this);
    }
    public function clearValues() {
        foreach ($this->values->getKeys() as $key) {
            $this->values->remove($key);
        }
    }
    public function removeValue($value) {
        $this->values->removeElement($value);
    }

    /**
     * @OneToMany(targetEntity="\Pasteque\Server\Model\PaymentModeReturn", mappedBy="paymentMode", cascade={"all"})
     * @OrderBy({"minAmount" = "DESC"})
     */
    // Warning: no orphan removal as it doesn't work with Mysql. This is handled in PaymentModeAPI.
    protected $returns;
    public function getReturns() { return $this->returns; }
    public function setReturns($returns) {
        $this->clearReturns();
        foreach ($returns as $return) {
            $this->addReturn($return);
        }
    }
    public function addReturn($return) {
        $this->returns->add($return);
        $return->setPaymentMode($this);
        if ($return->getReturnMode() === null) {
            $return->setReturnMode($this);
        }
    }
    public function clearReturns() {
        foreach ($this->returns->getKeys() as $key) {
            $this->returns->remove($key);
        }
    }
    public function removeReturn($return) {
        $this->returns->removeElement($return);
    }

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

    /**
     * Order of display of the payment mode
     * @var int order
     * @SWG\Property(format="int32")
     * @Column(type="integer", name="disp_order")
     */
    protected $dispOrder = 0;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

    /**
     * Is the payment mode currently active (visible on cash registers) ?
     * If invisible, the payment will still be useable in returns, but not
     * as main payment.
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $visible = true;
    public function getVisible() { return $this->visible; }
    /** Alias for getVisible (the latter is required for Doctrine) */
    public function isVisible() { return $this->getVisible(); }
    public function setVisible($visible) { $this->visible = $visible; }

    public function toStruct() {
        $struct = parent::toStruct();
        // Set code for compatibility
        $struct['code'] = $this->getReference();
        return $struct;
    }

    /**
     * Convert data from the old desktop client which uses type instead of id
     * in Z tickets.
     * @throws RecordNotFoundException When no record is found with the given
     * 'type' with the 'desktop' mode.
     */
    public static function convertIdFromType($struct, $dao) {
        if (!empty($struct['desktop'])) {
            $paymentMode = \Pasteque\Server\Model\PaymentMode::load($struct['type'], $dao);
            if ($paymentMode !== null) {
                $struct['paymentMode'] = $paymentMode->getId();
            } else {
                throw new RecordNotFoundException(PaymentMode::class,
                        $struct['type']);
            }
            unset($struct['type']);
            unset($struct['desktop']);
        }
        return $struct;
    }


    public function merge($struct, $dao) {
        parent::merge($struct, $dao);
        // Set reference from code for compatibility
        if (!empty($struct['code']) && empty($struct['reference'])) {
            $this->setReference($struct['code']);
        }
    }
}
