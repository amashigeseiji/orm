<?php
namespace tenjuu99\ORM;

use Aura\SqlQuery\Common\Select;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use tenjuu99\ORM\Pagination;
use Countable;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use Ray\Di\Di\PostConstruct;

abstract class AbstractRepository implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var string|null */
    protected $from;
    /** @var array */
    private $where = [];
    /** @var array */
    private $join = [];

    /** @var array|null */
    private $cols;
    /** @var array */
    private $condition = [];

    /** @var array */
    private $with = [];

    /** @var bool */
    private $paging = false;
    /** @var int */
    private $page;
    /** @var int */
    private $pageSize;

    /** @var array */
    private $groupBy = [];
    /** @var string|null */
    private $having;
    /** @var array|null */
    private $orderBy;

    /** @var array */
    private $schemaCache = [];

    /** @var TableSchema */
    private $schema;
    /** @var EntityFactory */
    private $entity;
    /** @var DbConnectionInterface */
    private $conn;
    /** @var QueryFactory */
    private $queryFactory;

    public function __construct(DbConnectionInterface $conn, TableSchema $schema, QueryFactory $factory, EntityFactory $entity)
    {
        $this->conn = $conn;
        $this->schema = $schema;
        $this->queryFactory = $factory;
        $this->entity = $entity;
    }

    /**
     * @PostConstruct
     */
    public function initialize() : void
    {
        $class = static::class;
        if (strpos($class, '_') !== false) {
            $class = explode('_', $class)[0];
        }
        $class = preg_replace('/Repository$/', '', $class);
        if (class_exists($class)) {
            $this->entity->setClass($class);
        }
    }

    /**
     * @return static
     */
    public function cols(array $cols) : self
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function where(string $column, $value) : self
    {
        $placeholder = str_replace('.', '_', $column);

        $this->where[] = "{$column} = :{$placeholder}";

        $this->setCondition($placeholder, $value);

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
        if (is_string($column)) {
            $placeholder = str_replace('.', '_', $column);

            $this->where[] = "{$column} LIKE :{$placeholder}";

            $this->setCondition($placeholder, '%' . $value . '%');
        } else {
            $stmt = [];
            foreach ($column as $col) {
                $placeholder = str_replace('.', '_', $col);
                $stmt[] = "{$col} LIKE :{$placeholder}";
                $this->setCondition($placeholder, '%' . $value . '%');
            }
            $this->where[] = '(' . implode(' OR ', $stmt) . ')';
        }

        return $this;
    }

    /**
     * @return static
     */
    public function whereIn(string $column, array $values) : self
    {
        $base = str_replace('.', '_', $column);
        $placeholders = [];
        $count = 0;
        foreach ($values as $value) {
            $placeholder = $base . '_' . (string) $count;
            $placeholders[] = ':' . $placeholder;
            $this->setCondition($placeholder, $value);
            $count++;
        }

        $this->where[] = "{$column} IN (" . implode(',', $placeholders) . ')';

        return $this;
    }

    /**
     * @param string     $column 対象カラム
     * @param string|int $from   範囲指定の始点
     * @param string|int $to     範囲指定の終点
     *
     * @return static
     */
    public function whereBetween(string $column, $from, $to) : self
    {
        $base = str_replace('.', '_', $column);

        $placeholderFrom = $base . '_from';
        $placeholderTo = $base . '_to';
        $this->where[] = "{$column} BETWEEN :{$placeholderFrom} AND :{$placeholderTo}";
        $this->setCondition($placeholderFrom, $from);
        $this->setCondition($placeholderTo, $to);

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
        $this->with = array_merge($this->with, $joins);

        return $this;
    }

    /**
     * @return static
     */
    public function groupBy(array $column) : self
    {
        $this->groupBy = $column;

        return $this;
    }

    /**
     * @return static
     */
    public function having(string $having) : self
    {
        $this->having = $having;

        return $this;
    }

    /**
     * @return static
     */
    public function orderBy(array $orderBy) : self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getIterator()
    {
        $result = $this->getResult($this->cols, true);
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

    /**
     * @return static
     */
    protected function join(string $table, string $condition = null, array $bind = [], string $joinType = 'LEFT') : self
    {
        $this->join[$table] = [
            'table' => $table,
            'cond' => $condition,
            'bind' => $bind,
            'joinType' => $joinType
        ];

        return $this;
    }

    protected function perform(QueryInterface $query, array $bind = []) : \PDOStatement
    {
        $result = $this->conn->perform(
            (string) $query,
            $bind
        );
        return $result;
    }

    private function getResult(array $column = null, bool $paging = false) : \PDOStatement
    {
        $query = $this->buildQuery()->cols($column ?: $this->defaultColumns());
        if ($paging) {
            $query->setPaging($this->pageSize)->page($this->page);
        }

        return $this->perform($query, $this->condition);
    }

    public function clear() : void
    {
        $this->where = [];
        $this->with = [];
        $this->groupBy = [];
        $this->having = '';
        $this->orderBy = [];
        $this->cols = [];
        $this->condition = [];
    }

    /**
     * @param mixed $value
     */
    private function setCondition(string $key, $value) : void
    {
        $this->condition[$key] = $value;
    }

    private function buildQuery() : Select
    {
        if (! $this->from) {
            throw new Exception('from が未定義です');
        }

        /** @var \Aura\SqlQuery\Common\Select */
        $query = $this->queryFactory->newSelect()->from($this->from);
        if ($this->with) {
            foreach ($this->with as $joinName) {
                if (! isset($this->join[$joinName])) {
                    continue;
                }
                $join = $this->join[$joinName];
                $query->join($join['joinType'], $join['table'], $join['cond'], $join['bind']);
                if ($join['bind']) {
                    foreach ($join['bind'] as $key => $val) {
                        $this->setCondition($key, $val);
                    }
                }
            }
        }
        foreach ($this->where as $where) {
            $query->where($where);
        }
        if ($this->groupBy) {
            $query->groupBy($this->groupBy);
            if ($this->having) {
                $query->having($this->having);
            }
        }
        if ($this->orderBy) {
            $query->orderBy($this->orderBy);
        }

        return $query;
    }

    private function defaultColumns() : array
    {
        if (! $this->from) {
            throw new Exception('from が未定義です');
        }
        $columns = $this->getSelectColumnsFromTable($this->from, $this->from);
        if ($this->with) {
            foreach ($this->with as $join) {
                if (! isset($this->join[$join])) {
                    continue;
                }
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

    public function jsonSerialize()
    {
        return iterator_to_array($this);
    }
}
