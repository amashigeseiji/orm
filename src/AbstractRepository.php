<?php

namespace tenjuu99\ORM;

use Aura\SqlQuery\Common\Select;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use Countable;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use Ray\Di\Di\PostConstruct;

abstract class AbstractRepository implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var null|string */
    protected $from;

    /** @var Criteria */
    private $criteria;

    /** @var bool */
    private $paging = false;
    /** @var int */
    private $page;
    /** @var int */
    private $pageSize;

    /** @var array */
    private $with = [];

    /** @var TableSchema */
    private $schema;
    /** @var EntityFactory */
    private $entity;
    /** @var DbConnectionInterface */
    private $conn;
    /** @var QueryFactory */
    private $queryFactory;

    public function __construct(DbConnectionInterface $conn, TableSchema $schema, QueryFactory $factory, EntityFactory $entity, Criteria $criteria)
    {
        $this->conn = $conn;
        $this->schema = $schema;
        $this->queryFactory = $factory;
        $this->entity = $entity;
        $this->criteria = $criteria;
    }

    /**
     * @PostConstruct
     */
    public function initialize() : void
    {
        $this->entity->setClass($this->assign);
    }

    /**
     * @return static
     */
    public function cols(array $cols) : self
    {
        $this->criteria->cols($cols);

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function where(string $column, $value) : self
    {
        $this->criteria->where($column, $value);

        return $this;
    }

    /**
     * @param array|string $column 配列指定の場合 (`col_a` LIKE %search% or `col_b` LIKE %search%)
     *                             文字列指定の場合 `col_a` LIKE %search%
     * @param mixed        $value
     *
     * @return static
     */
    public function like($column, $value) : self
    {
        $this->criteria->like($column, $value);

        return $this;
    }

    /**
     * @return static
     */
    public function whereIn(string $column, array $values) : self
    {
        $this->criteria->whereIn($column, $values);

        return $this;
    }

    /**
     * @param string     $column 対象カラム
     * @param int|string $from   範囲指定の始点
     * @param int|string $to     範囲指定の終点
     *
     * @return static
     */
    public function whereBetween(string $column, $from, $to) : self
    {
        $this->criteria->whereBetween($column, $from, $to);

        return $this;
    }

    /**
     * @return static
     */
    public function paging(int $page, int $pageSize) : self
    {
        $this->paging = true;
        $this->page = $page;
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * @return static
     */
    public function with(string ...$joins) : self
    {
        $this->criteria->with(...$joins);
        $this->with = array_merge($this->with, $joins);

        return $this;
    }

    /**
     * @return static
     */
    public function groupBy(array $column) : self
    {
        $this->criteria->groupBy($column);

        return $this;
    }

    /**
     * @return static
     */
    public function having(string $having) : self
    {
        $this->criteria->having($having);

        return $this;
    }

    /**
     * @return static
     */
    public function orderBy(array $orderBy) : self
    {
        $this->criteria->orderBy($orderBy);

        return $this;
    }

    public function getIterator()
    {
        $result = $this->getResult($this->criteria->cols, true);
        while ($item = $result->fetch()) {
            yield $this->entity->create($item);
        }
    }

    /**
     * @return mixed
     */
    public function fetchOne(array $columns = null)
    {
        $result = $this->getResult($columns)->fetch();

        return $this->entity->create($result);
    }

    public function count() : int
    {
        return $this->getResult(['count(*) AS CNT'])->fetch()['CNT'];
    }

    public function getPagination(string $uri) : Pagination
    {
        return new Pagination(
            $uri,
            count($this),
            $this->page,
            $this->pageSize
        );
    }

    /**
     * @return static
     */
    public function assign(?string $class) : self
    {
        $this->entity->setClass($class);

        return $this;
    }

    public function clear() : void
    {
        $this->criteria->clear();
    }

    public function jsonSerialize()
    {
        return iterator_to_array($this);
    }

    /**
     * @return static
     */
    protected function join(string $table, string $condition = null, array $bind = [], string $joinType = 'LEFT') : self
    {
        $this->criteria->join($table, $condition, $bind, $joinType);

        return $this;
    }

    protected function perform(QueryInterface $query, array $bind = []) : \PDOStatement
    {
        return $this->conn->perform(
            (string) $query,
            $bind
        );
    }

    private function getResult(array $column = null, bool $paging = false) : \PDOStatement
    {
        $query = $this->buildQuery()->cols($column ?: $this->defaultColumns());
        if ($paging) {
            $query->setPaging($this->pageSize)->page($this->page);
        }

        return $this->perform($query, $this->criteria->condition);
    }

    private function buildQuery() : Select
    {
        if (! $this->from) {
            throw new Exception('from が未定義です');
        }

        /** @var \Aura\SqlQuery\Common\Select */
        $query = $this->queryFactory->newSelect()->from($this->from);

        return $this->criteria->build($query);
    }

    private function defaultColumns() : array
    {
        if (! $this->from) {
            throw new Exception('from が未定義です');
        }
        $columns = $this->getSelectColumnsFromTable($this->from, $this->from);
        if ($this->with) {
            foreach ($this->with as $join) {
                $joinTable = $this->getSelectColumnsFromTable($join, $join);
                $columns = array_merge($columns, $joinTable);
            }
        }

        return $columns;
    }

    private function getSelectColumnsFromTable(string $tableName, string $alias) : array
    {
        $columns = $this->schema->getColumns($tableName);

        return array_map(function ($value) use ($alias) {
            return $alias . '.' . $value['name'] . ' AS ' . $alias . '__' . $value['name'];
        }, $columns);
    }
}
