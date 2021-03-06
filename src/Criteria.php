<?php

namespace tenjuu99\ORM;

use Aura\SqlQuery\Common\Select;

class Criteria
{
    /** @var null|array */
    public $cols;

    /** @var array */
    public $condition = [];
    /** @var array */
    private $where = [];

    /** @var array */
    private $join = [];
    /** @var array */
    private $with = [];

    /** @var array */
    private $groupBy = [];
    /** @var null|string */
    private $having;
    /** @var null|array */
    private $orderBy;

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
            ++$count;
        }

        $this->where[] = "{$column} IN (" . implode(',', $placeholders) . ')';

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
    public function join(string $table, string $condition = null, array $bind = [], string $joinType = 'LEFT') : self
    {
        $this->join[$table] = [
            'table' => $table,
            'cond' => $condition,
            'bind' => $bind,
            'joinType' => $joinType,
        ];

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

    public function build(Select $query) : Select
    {
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

    /**
     * @param mixed $value
     */
    private function setCondition(string $key, $value) : void
    {
        $this->condition[$key] = $value;
    }
}
