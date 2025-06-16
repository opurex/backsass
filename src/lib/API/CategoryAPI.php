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

use \Pasteque\Server\System\DAO\DAOCondition;

/** CRUD API for Category */
class CategoryAPI extends APIRefHelper
{
    const MODEL_NAME = 'Pasteque\Server\Model\Category';
    const DEFAULT_ORDER = ['dispOrder', 'reference'];

    private function fillChildrenCategories($cat, &$result) {
        $children = $this->dao->search(static::MODEL_NAME,
                new DAOCondition('parent', '=', $cat));
        foreach ($children as $child) {
            $result[] = $child;
            $this->fillChildrenCategories($child, $result);
        }
    }

    /** Delete a category and all its content (children and products). */
    public function deleteRecursive($id) {
        if (!is_array($id)) {
            $id = array($id);
        }
        $allCats = array();
        $references = array();
        foreach ($id as $i) {
            $model = $this->dao->read(static::MODEL_NAME, $i);
            if ($model != null) {
                $references[] = $model;
                $allCats[] = $model;
            }
        }
        foreach ($references as $cat) {
            $this->fillChildrenCategories($cat, $allCats);
        }
        // TODO: delete products
        // Delete categories, starting from bottom (children)
        for ($i = count($allCats) - 1; $i >= 0; $i--) {
            $this->dao->delete($allCats[$i]);
        }
        $this->dao->commit();
        return (count($allCats));
    }
}
