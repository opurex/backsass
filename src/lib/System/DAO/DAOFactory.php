<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
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

/** Utility class to setup DAO. */
class DAOFactory
{
    /**
     * Get a DAO object.
     * @param $dbInfo Database connexion info from a DBModule
     * @param $options Additionnal options in an associative array
     * for the DAO. Allowed options are 'debug' (true/false).
     * @return DoctrineDAO The DAO according to the configuration.
     */
    public static function getDAO($dbInfo, $options) {
        // This prevents spreading references to DoctrineDAO everywhere in API
        return new DoctrineDAO($dbInfo, $options);
    }

}
