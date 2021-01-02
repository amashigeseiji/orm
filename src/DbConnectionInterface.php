<?php

namespace tenjuu99\ORM;

use PDOStatement;

interface DbConnectionInterface
{
    /**
     * Queries the database and returns a PDOStatement.
     *
     * @param string $statement the SQL statement to prepare and execute
     * @param mixed  ...$fetch  Optional fetch-related parameters.
     *
     * @see http://php.net/manual/en/pdo.query.php
     */
    public function query($statement, ...$fetch) : PDOStatement;

    /**
     * Performs a query after preparing the statement with bound values, then
     * returns the result as a PDOStatement.
     *
     * @param string $statement the SQL statement to prepare and execute
     * @param array  $values    values to bind to the query
     *
     * @return \PDOStatement
     */
    public function perform($statement, array $values = []);
}
