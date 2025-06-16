<?php
//    Pasteque API
//
//    Copyright (C) 2012-2017 Pasteque contributors
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\CommonAPI;

use \Pasteque\Server\Model\GenericModel;
use \Pasteque\Server\Model\Option;

class VersionAPI implements \Pasteque\Server\API\API {

    const VERSION = '8.9';
    const REVISION = 4;
    const LEVEL = 8;

    protected $dao;

    public function __construct($dao) {
        $this->dao = $dao;
    }

    public static function fromApp($app) {
        return new VersionAPI($app->getDao());
    }

    public function get() {
        $version = $this->dao->read(Option::class, 'dblevel');
        if ($version === null) {
            // Database is not initialized.
            throw new \UnexpectedValueException('dblevel option does not exist. The database is probably not initialized.');
        }
        $dbLevel = intVal($version->getContent());
        if ($dbLevel === 0) {
            throw new \UnexpectedValueException(sprintf('dblevel %s option is invalid.', $version->getContent()));
        }
        $version = new GenericModel();
        $version->set('version', static::VERSION);
        $version->set('level', $dbLevel);
        $version->set('revision', static::REVISION);
        return $version;
    }

    /** Update the database level. It should be used only by update scripts
     * and not available outside the server.
     * If the dblevel option does not exists, it is created. */
    public function setLevel($newLevel) {
        if (is_int($newLevel)) {
            $newLevel = strval($newLevel);
        }
        if (empty($newLevel)) {
            throw new \InvalidArgumentException('Invalid level value.');
        }
        $version = $this->dao->read(Option::class, 'dblevel');
        if ($version === null) {
            $version = new Option();
            $version->setName('dblevel');
            $version->setSystem(true);
        }
        $version->setContent($newLevel);
        $this->dao->write($version);
        $this->dao->commit();
    }
}
