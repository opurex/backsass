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

use \Pasteque\Server\Model\Category;
use \Pasteque\Server\Model\Tax;
use \Pasteque\Server\Model\Field\BoolField;
use \Pasteque\Server\Model\Field\EnumField;
use \Pasteque\Server\Model\Field\IntField;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Product
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="products")
 */
class Product extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return ['id',
                new StringField('reference', ['autosetFrom' => 'label']),
                new StringField('label'),
                new StringField('barcode'),
                new FloatField('priceBuy', ['nullable' => true]),
                new FloatField('priceSell'),
                new BoolField('visible'),
                new BoolField('scaled'),
                new EnumField('scaleType',
                        ['values' => [static::SCALE_TYPE_NONE,
                        static::SCALE_TYPE_WEIGHT, static::SCALE_TYPE_VOLUME,
                        static::SCALE_TYPE_TIME]]),
                new FloatField('scaleValue'),
                new IntField('dispOrder'),
                new BoolField('discountEnabled'),
                new FloatField('discountRate'),
                new BoolField('prepay'),
                new BoolField('composition'),
                'hasImage'];
    }
    protected static function getAssociationFields() {
        return [
                [
                 'name' => 'category',
                 'class' => '\Pasteque\Server\Model\Category'
                 ],
                [
                 'name' => 'tax',
                 'class' => '\Pasteque\Server\Model\Tax'
                 ],
                [
                 'name' => 'compositionGroups',
                 'class' => '\Pasteque\Server\Model\CompositionGroup',
                 'array' => true,
                 'embedded' => true,
                 ]
                ];
    }
    protected static function getReferenceKey() {
        return 'reference';
    }

    public function __construct() {
        $this->compositionGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * ID of the product
     * @var integer
     * @SWG\Property()
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Code of the product, user-friendly ID.
     * It is automatically set from label if not explicitely set.
     * @var string
     * @SWG\Property()
     * @Column(type="string", unique=true)
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($reference) { $this->reference = $reference; }

    /**
     * barcode of a product
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $barcode = '';
    public function getBarcode() {
        return $this->barcode;
    }
    public function setBarcode($barcode) {
        $this->barcode = $barcode;
    }

    /**
     * name of a product
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $label;
    public function getLabel() { return $this->label; }
    public function setLabel($label) {
        $this->label = $label;
        if ($this->getReference() === null) {
            $this->setReference($label);
        }
    }

    /**
     * Buy price without taxes, used for estimated margin computation.
     * @var float
     * @SWG\Property()
     * @Column(type="float", nullable=true)
     */
    protected $priceBuy = null;
    public function getPriceBuy() { return round($this->priceBuy, 5); }
    public function setPriceBuy($priceBuy) { $this->priceBuy = round($priceBuy, 5); }

    /**
     * sell price without taxes.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $priceSell;
    public function getPriceSell() { return round($this->priceSell, 5); }
    public function setPriceSell($price) { $this->priceSell = round($price, 5); }

    /**
     * Is product currently in sale (visible on cash registers) ?
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $visible = true;
    public function getVisible() { return $this->visible; }
    /** Alias for getVisible (the latter is required for Doctrine) */
    public function isVisible() { return $this->getVisible(); }
    public function setVisible($visible) { $this->visible = $visible; }

    /**
     * Is the product sold by scale?
     * isScale can be false with a SCALE_TYPE_WEIGHT (i.e. a box of 200g).
     * When isScale is true, scaleValue is meaningless.
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $scaled = false;
    public function getScaled() { return $this->scaled; }
    /** Alias for getScaled (the latter is required for Doctrine). */
    public function isScaled() { return $this->scaled; }
    public function setScaled($scaled) { $this->scaled = $scaled; }

    /** Constant for scaleType, product is atomical (mapped to 0). */
    const SCALE_TYPE_NONE = 0;
    /** Constant for scaleType, product is referenced by weight (mapped to 1). */
    const SCALE_TYPE_WEIGHT = 1;
    /** Constant for scaleType, product is referenced by volume (mapped to 2). */
    const SCALE_TYPE_VOLUME = 2;
    /** Constant for scaleType, product is referenced by time (mapped to 3). */
    const SCALE_TYPE_TIME = 3;
    /**
     * See SCALE_TYPE_* constants.
     * Used to compute reference prices like price per liter or kg.
     * @var int
     * @SWG\Property()
     * @Column(type="smallint")
     */
    protected $scaleType = Product::SCALE_TYPE_NONE;
    public function getScaleType() { return $this->scaleType; }
    public function setScaleType($type) {
        if ($type != Product::SCALE_TYPE_NONE
            && $type != Product::SCALE_TYPE_WEIGHT
            && $type != Product::SCALE_TYPE_VOLUME
            && $type != Product::SCALE_TYPE_TIME) {
            throw new \InvalidArgumentException('Unknown scaleType');
        }
        $this->scaleType = $type;
    }

    /**
     * The scale value for products referenced by weight or volume and
     * not soled by scale.
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $scaleValue = 1.0;
    public function getScaleValue() { return round($this->scaleValue, 5); }
    public function setScaleValue($value) { $this->scaleValue = round($value, 5); }

    /**
     * ID of the category
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="Category")
     * @JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     */
    protected $category;
    public function getCategory() { return $this->category; }
    public function setCategory($category) { $this->category = $category; }

    /**
     * Order of display inside it's category
     * @var int order
     * @SWG\Property(format="int32")
     * @Column(type="integer", name="disp_order")
     */
    protected $dispOrder = 0;
    public function getDispOrder() { return $this->dispOrder; }
    public function setDispOrder($dispOrder) { $this->dispOrder = $dispOrder; }

    /**
     * ID of a tax
     * @var integer
     * @SWG\Property()
     * @ManyToOne(targetEntity="Tax")
     * @JoinColumn(name="tax_id", referencedColumnName="id", nullable=false)
     */
    protected $tax;
    public function getTax() { return $this->tax; }
    public function setTax($tax) { $this->tax = $tax; }

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
     * Is discount currently enabled ?
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $discountEnabled = false;
    public function getDiscountEnabled() { return $this->discountEnabled; }
    /** Alias for getDiscountEnabled (the latter is required for Doctrine). */
    public function isDiscountEnabled() { return $this->getDiscountenabled(); }
    public function setDiscountEnabled($discountEnabled) { $this->discountEnabled = $discountEnabled; }

    /**
     * rate of the discount
     * @var float
     * @SWG\Property()
     * @Column(type="float")
     */
    protected $discountRate = 0.0;
    public function getDiscountRate() { return $this->discountRate; }
    public function setDiscountRate($rate) { $this->discountRate = $rate; }

    /**
     * Is product a prepayment refill?
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $prepay = false;
    public function getPrepay() { return $this->prepay; }
    public function isPrepay() { return $this->getPrepay(); }
    public function setPrepay($prepay) { $this->prepay = $prepay; }

    /**
     * Is product a composition?
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $composition = false;
    public function getComposition() { return $this->composition; }
    public function isComposition() { return $this->getComposition(); }
    public function setComposition($composition) { $this->composition = $composition; }

    /**
     * @OneToMany(targetEntity="\Pasteque\Server\Model\CompositionGroup", mappedBy="product", cascade={"persist"}, orphanRemoval=true)
     * @OrderBy({"dispOrder" = "ASC"}) */
    protected $compositionGroups;
    public function getcompositionGroups() { return $this->compositionGroups; }
    public function setCompositionGroups($compositionGroups) {
        $this->compositionGroups->clear();
        foreach ($compositionGroups as $compositionGroup) {
            $this->addCompositionGroup($compositionGroup);
        }
    }
    public function clearCompositionGroups() {
        $this->getCompositionGroups()->clear();
    }
    public function addCompositionGroup($compositionGroup) {
        $this->compositionGroups->add($compositionGroup);
        $compositionGroup->setProduct($this);
    }
    public function removeCompositionGroup($compositionGroup) {
        $this->compositionGroups->removeElement($compositionGroup);
        $compositionGroup->setProduct(null);
    }

    /** Get sell price with tax.
     * Virtual field passed along toStruct at current time.*/
    public function getTaxedPrice() {
        $tax = $this->getTax();
        $price = $this->getPriceSell();
        return round($price * (1 + $tax->getRate()), 2);
    }

    public function getTaxValue() {
        return round($this->getTaxedPrice() - $this->getPriceSell(), 2);
    }

    public function toStruct() {
        $struct = parent::toStruct();
        $struct['taxedPrice'] = $this->getTaxedPrice();
        $struct['taxValue'] = $this->getTaxValue();
        if ($this->isPrepay()) {
            // Embed prepay value to be able to apply discounts on it.
            $struct['prepayValue'] = $this->getPriceSell();
        }
        if (!$this->isComposition()) {
            unset($struct['compositionGroups']);
        }
        return $struct;
    }
}
