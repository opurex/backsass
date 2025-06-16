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

namespace Pasteque\Server\System\DAO;

/** Condition object to pass to DAO->search. */
class DAOCondition
{
    private $fieldName;
    private $operator;
    private $value;

    /**
     * Create a search condition.
     * @param string $fieldName Property name of the model to filter on.
     * This is not the database field name.
     * @param string $operator Either '=', '!=' , '>', '>=', '<' or '<='.
     * <field> '=' null is a valid condition as well as '!=' null.
     * @param mixed $value The value to use with the operator.
     */
    public function __construct($fieldName, $operator, $value) {
        $this->fieldName = $fieldName;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getFieldName() { return $this->fieldName; }
    public function getOperator() { return $this->operator; }
    public function getValue() { return $this->value; }
}
