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

namespace Pasteque\Server\API;

use \Pasteque\Server\Model\Category;
use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for Product */
class ProductAPI extends APIRefHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\Product';
    const DEFAULT_ORDER = ['dispOrder', 'label'];

    private static $VISIBLE_CONDITION = null;
    private static $ARCHIVE_CONDITION = null;
    private static function visibleCondition() {
        if (static::$VISIBLE_CONDITION === null) {
            static::$VISIBLE_CONDITION = new DAOCondition('visible', '=', true);
        }
        return static::$VISIBLE_CONDITION;
    }
    private static function archiveCondition() {
        if (static::$ARCHIVE_CONDITION === null) {
            static::$ARCHIVE_CONDITION = new DAOCondition('visible', '=', false);
        }
        return static::$ARCHIVE_CONDITION;
    }

    /** Get all the visible (sellable) products, ignore the others. */
    public function getAllVisible($order = null) {
        $order = $this->getOrder($order);
        return $this->dao->search(static::MODEL_NAME,
                static::visibleCondition(), null, null, $order);
    }

    /** Get all Products currently not in sold. */
    public function getArchive() {
        $order = $this->getOrder(null);
        return $this->dao->search(static::MODEL_NAME,
                static::archiveCondition(), null, null, $order);
    }

    /** Get all products from a category, excluding archive.
     * @param $category Id or Category object to get products from.
     * Valid Id is an int or a numeric string.
     * @throws \InvalidArgumentException If $category is not of valid type. */
    public function getFromCategory($category) {
        $order = $this->getOrder(null);
        if (is_numeric($category)) {
            $cat = $this->dao->read(Category::class, intval($category));
            if ($cat != null) {
                return $this->dao->search(static::MODEL_NAME,
                        [new DAOCondition('category', '=', $cat),
                         static::visibleCondition()], null, null, $order);
            }
        } else if (is_object($category) && is_a($category, Category::class)) {
            return $this->dao->search(static::MODEL_NAME,
                    [new DAOCondition('category', '=', $category),
                     static::visibleCondition()], null, null, $order);
        } else {
            throw new \InvalidArgumentException('Incompatible category type');
        }
    }

    /** Get a single entry from it's barcode. If multiple products share
     * the same barcode, returns only one. */
    public function getByCode($code) {
        $data = $this->dao->search(static::MODEL_NAME, new DAOCondition('barcode', '=', $code));
        if (count($data) > 0) {
            return $data[0];
        }
        return null;
    }
}
