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

namespace Pasteque\Server\Model;

use \Pasteque\Server\Model\Field\BoolField;
use \Pasteque\Server\Model\Field\FloatField;
use \Pasteque\Server\Model\Field\StringField;
use \Pasteque\Server\System\DAO\DoctrineMainModel;

/**
 * Class Currency
 * @package Pasteque
 * @SWG\Definition(type="object")
 * @Entity
 * @Table(name="currencies")
 */
class Currency extends DoctrineMainModel
{
    protected static function getDirectFieldNames() {
        return [
                new StringField('reference'),
                new StringField('label'),
                new StringField('symbol'),
                new StringField('decimalSeparator'),
                new StringField('thousandsSeparator'),
                new StringField('format'),
                new FloatField('rate'),
                new BoolField('main'),
                new BoolField('visible')
                ];
    }
    protected static function getAssociationFields() {
        return [];
    }
    protected static function getReferenceKey() {
        return 'reference';
    }

    /**
     * Id of the Currency
     * @var int
     * @SWG\Property(format="int32")
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    public function getId() { return $this->id; }

    /**
     * Code of the currency, user-friendly ID.
     * It is automatically set from label if not explicitely set.
     * @var string
     * @SWG\Property()
     * @Column(type="string", unique=true)
     */
    protected $reference;
    public function getReference() { return $this->reference; }
    public function setReference($reference) { $this->reference = $reference; }

    /**
     * Name of the currency
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
     * Symbol of the currency
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $symbol = '¤';
    public function getSymbol() { return $this->symbol; }
    public function setSymbol($symbol) { $this->symbol = $symbol; }

    /**
     * Decimal separator of the currency
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $decimalSeparator = '';
    public function getDecimalSeparator() { return $this->decimalSeparator; }
    public function setDecimalSeparator($decimalSeparator) { $this->decimalSeparator = $decimalSeparator; }

    /**
     * Thousands separator of the currency
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $thousandsSeparator = '';
    public function getThousandsSeparator() { return $this->thousandsSeparator; }
    public function setThousandsSeparator($thousandsSeparator) { $this->thousandsSeparator = $thousandsSeparator; }

    /**
     * Format of the currency
     * @var string
     * @SWG\Property()
     * @Column(type="string")
     */
    protected $format = '#.##0.00¤';
    public function getFormat() { return $this->format; }
    public function setFormat($format) { $this->format = $format; }

    /**
     * Rate of the currency, regarding the main currency
     * @var float
     * @SWG\Property(format="double")
     * @Column(type="float")
     */
    protected $rate = 1.0;
    public function getRate() { return round($this->rate, 5); }
    public function setRate($price) { $this->rate = round($price, 5); }

    /**
     * Is the currency the reference one?
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $main = false;
    public function getMain() { return $this->main; }
    /** Alias for getMain (the latter is required for Doctrine) */
    public function isMain() { return $this->getMain(); }
    public function setMain($main) { $this->main = $main; }

    /**
     * Is the currency currently useable (visible on cash registers) ?
     * @var bool
     * @SWG\Property()
     * @Column(type="boolean")
     */
    protected $visible = true;
    public function getVisible() { return $this->visible; }
    /** Alias for getVisible (the latter is required for Doctrine) */
    public function isVisible() { return $this->getVisible(); }
    public function setVisible($visible) { $this->visible = $visible; }

}
