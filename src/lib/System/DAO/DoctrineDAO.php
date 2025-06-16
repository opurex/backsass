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

use \Doctrine\Common\Collections\Criteria;
use \Doctrine\ORM\EntityManager;
use \Doctrine\ORM\Tools\Setup;

/** DAO using Doctrine. */
class DoctrineDAO implements DAO
{
    protected $em;
    /** Duplicated entity manager required to fuck off the in-memory cache. */
    protected $roEm;
    protected $inTransaction;
    /** Holds the ids of models deleted during the transaction and not commited.
     * delete/read without commit makes Doctrine go in a deadlock.
     * Contains the deleted model indexed by the full ID string.
     * Emptied on commit. */
    protected $deletedIds;

    private static function getDriverName($type) {
        switch (strtolower($type)) {
            case 'mysql': return 'pdo_mysql';
            case 'postgresql': return 'pdo_pgsql';
            case 'sqlite': return 'pdo_sqlite';
            default: throw new DBException(sprintf('Unsupported database type %s', $type), DBException::CODE_TYPE_ERROR);
        }
    }

    /**
     * Get a DoctrineDAO.
     * It does not try to connect to the database directly.
     * @throw DBException if the type is unknown or unsupported.
     */
    public function __construct($dbInfo, $options = array()) {
        $path = [__DIR__ . '/../../Model'];
        $isDevMode = (isset($options['debug'])) ? $options['debug'] : false;
        if (!array_key_exists('type', $dbInfo)) {
            throw new DBException('Not database type provided', DBException::CODE_TYPE_ERROR);
        }
        $driver = DoctrineDAO::getDriverName($dbInfo['type']);
        if ($dbInfo['type'] !== 'sqlite') {
            $dbParams = array('dbname' => $dbInfo['name'],
                    'user' => $dbInfo['user'],
                    'password' => $dbInfo['password'],
                    'host' => $dbInfo['host'],
                    'driver' => $driver,
                    'charset'=> 'utf8');
        } else {
            $dbParams = array('path' => $dbInfo['name'],
                    'driver' => $driver);
        }
        $setup = Setup::createAnnotationMetadataConfiguration($path, $isDevMode,
                __DIR__ . '/../../../generated/');

        $this->em = EntityManager::create($dbParams, $setup);
        $this->roEm = EntityManager::create($dbParams, $setup);
        $this->inTransaction = false;
        $this->deletedIds = [];
        // Enable foreign keys for sqlite
        if ($dbInfo['type'] == 'sqlite') {
            $this->em->getConnection()->query('PRAGMA foreign_keys = ON;');
        }
    }

    /** Get a full ID string for delete/read to use with deletedIds.
     * Has 2 signatures: getIdKey($model) and getIdKey($class, $id). */
    private function getIdKey() {
        $argc = func_get_args();
        if ($argc === 1) { return $model->getClass() . '__' . $model->getId(); }
        else if ($argc === 2) { return $model . '__' . $id; }
        else { return null; }
    }

    /**
     * Start a transaction.
     * It should be a private method and is automatically called through
     * write/delete calls, but it may be required for hacky tricks.
     * Do not use it unless starting updates from a dql request.
     */
    public function startTransaction() {
        $this->em->beginTransaction();
        $this->inTransaction = true;
    }

    /**
     * End the transaction and commit changes.
     * In case of error, rollback is done.
     * @throws \Exception When something bad happens. Automatically rollbacks.
     */
    public function commit() {
        if (!$this->inTransaction) { return; }
        try {
            $this->deletedIds = array();
            $this->em->flush();
            $this->em->commit();
            $this->inTransaction = false;
        } catch (\Exception $e) {
            // Reopen entity manager if it was closed because of a DB failure.
            if (!$this->em->isOpen()) {
                $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
            }
            $this->em->rollback();
            $this->inTransaction = false;
            throw $e;
        }
    }

    /**
     * Append the model to the transaction.
     * A transaction is automatically created if none is running.
     * End the transaction with commit().
     * @param DoctrineModel $model
     */
    public function write($model) {
        if (!$this->inTransaction) { $this->startTransaction(); }
        $this->em->persist($model);
    }

    /**
     * Read from database. May use caching.
     * @param string $modelName The class name of the model. See MODEL_NAME
     * in API classes.
     * @param mixed $id The database id. It may be a single value or an
     * associative array for composite keys.
     * @return DoctrineModel|null
     */
    public function read($modelName, $id) {
        if ($id === null) { return null; }
        return $this->em->find($modelName, $id);
    }

    /**
     * Force reading from database.
     * It skips reference caching and return the record as present in the
     * database, while read may return a modified entity from it's cache.
     * @param string $modelName The class name of the model. See MODEL_NAME
     * in API classes.
     * @param mixed $id The database id. It may be a single value or an
     * associative array for composite keys.
     * @return DoctrineModel|null
     */
    public function readSnapshot($modelName, $id) {
        if ($id === null) { return null; }
        $entity = $this->roEm->find($modelName, $id);
        if ($entity !== null) {
            $this->roEm->detach($entity);
        }
        return $entity;
    }

    /** Create a string to use with *where of a query builder. */
    private function getCondString($i ,$condition) {
        $field = $condition->getFieldName();
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        switch ($operator) {
            case '=':
                if ($value === null) {
                    return sprintf('model.%s is null', $field);
                } else {
                    return sprintf('model.%s = ?%d', $field, $i);
                }
            case '!=':
                if ($value === null) {
                    return sprintf('model.%s is not null', $field);
                } // else nobreak
            case '>':
            case '>=':
            case '<':
            case '<=':
                    return sprintf('model.%s %s ?%d', $field, $operator, $i);
            default:
                    throw new \InvalidArgumentException(sprintf('Unsupported Search operator %s'), $operator);
        }
    }

    private function bindCriteriaValue($qb, $i, $condition) {
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        if ($value === null && ($operator == '=' || $operator == '!=')) {
            return;
        }
        $qb->setParameter($i, $value);
    }

    private function setupQueryBuilder($qb, $conditions,
            $count = null, $offset = null, $order = null) {
        // Convert single condition to array for mixed signature
        if ($conditions === null) {
            $conditions = array();
        } else if (!is_array($conditions)) {
            $conditions = array($conditions);
        }
        // Create filter by conditions
        if (count($conditions) > 0) {
            for ($i = 0; $i < count($conditions); $i++) {
                if ($i == 0) {
                    $qb->where($this->getCondString($i, $conditions[$i]));
                } else {
                    $qb->andWhere($this->getCondString($i, $conditions[$i]));
                }
                $this->bindCriteriaValue($qb, $i, $conditions[$i]);
            }
        }
        // Set order by
        if ($order != null) {
            $doctrineOrder = array();
            if (!is_array($order)) {
                $order = array($order);
            }
            foreach ($order as $ord) {
                switch (substr($ord, 0, 1)) {
                    case '-':
                        $qb->addOrderBy(sprintf('model.%s', substr($ord, 1)),
                                'DESC');
                        break;
                    case '+':
                        $qb->addOrderBy(sprintf('model.%s', substr($ord, 1)),
                                'ASC');
                        break;
                    default:
                        $qb->addOrderBy(sprintf('model.%s', $ord), 'ASC');
                        break;
                }
            }
        }
        // Paging
        if ($count !== null) {
            $qb->setMaxResults($count);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
    }

    /**
     * Search records from the database.
     * @param string $modelName The class name of the model. See MODEL_NAME
     * in API classes.
     * @param DAOCondition|DAOCondition[] A single or array of DAOConditions.
     * When null, all records are returned.
     * @param int|null $count The maximum number of records to return. Use null
     * to disable the limit.
     * @param int|null $offset The starting offset for pagination. Use null
     * to disable it.
     * @param string|array $order The field names for ordering. Prefix the name
     * with '-' for a descending order, nothing or '+' for ascending order. Use
     * null for undefined ordering.
     * @return DoctrineModel[]
     */
    public function search($modelName, $conditions = null,
            $count = null, $offset = null, $order = null) {
        $optimizePagination = false;
        if ($count !== null || $offset !== null ) {
            $class = new \ReflectionClass($modelName);
            // Cannot auto-optimize if there is no 'id' field.
            if ($class->hasProperty('id')) {
                $optimizePagination = true;
            }
        }
        if (!$optimizePagination) {
            // The general non optimized query
            $qb = $this->em->createQueryBuilder();
            $qb->select('model')->from($modelName, 'model');
            $this->setupQueryBuilder($qb, $conditions, $count, $offset, $order);
            $query = $qb->getQuery();
            return $query->getResult();
        }
        // Optimize pagination by fetching id to hydrate only the relevant
        // records instead of everything then skipping anwanted one.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('model.id')->from($modelName, 'model');
        $this->setupQueryBuilder($qb, $conditions, $count, $offset, $order);
        $query = $qb->getQuery();
        $idFetch = $query->getArrayResult();
        $ids = [];
        foreach ($idFetch as $id) {
            $ids[] = $id['id'];
        }
        if (count($ids) == 0) {
            return [];
        }
        // Get the actual records from ids and return them.
        $qb2 = $this->getEntityManager()->createQueryBuilder();
        $qb2->select('model')->from($modelName, 'model');
        $qb2->where($qb->expr()->in('model.id', $ids));
        $query2 = $qb2->getQuery();
        return $query2->getResult();

    }

    /**
     * Count the number of records matching a search.
     * @param string $modelName The class name of the model. See MODEL_NAME in
     * API classes.
     * @param DAOCondition|DAOCondition[] $conditions The search criterias.
     * @return int The number of matching records.
     */
    public function count($modelName, $conditions = array()) {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(model)')->from($modelName, 'model');
        $this->setupQueryBuilder($qb, $conditions);
        $query = $qb->getQuery();
        $result = $query->getSingleResult();
        return $result[1]; // doctrine indexes results starting from 1
    }

    public function delete($model) {
        if ($model->getId() === null) { return; }
        if (!$this->inTransaction) { $this->startTransaction(); }
        $this->em->remove($model);
    }

    public function close() {
        $this->em->close();
        $this->roEm->close();
        $this->em->getConnection()->close();
        $this->roEm->getConnection()->close();
    }

    /**
     * Access to low-level implementation.
     * Should be used only for unit testing purposes.
     */
    public function getEntityManager() {
        return $this->em;
    }
}
