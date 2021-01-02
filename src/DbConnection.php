<?php

namespace tenjuu99\ORM;

use PDO;
use PDOStatement;

class DbConnection implements DbConnectionInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function query($statement, ...$fetch) : PDOStatement
    {
        return $this->pdo->query($statement, ...$fetch);
    }

    public function perform(string $statement, array $values = []) : PDOStatement
    {
        $stmt = $this->pdo->prepare($statement);
        $stmt->execute($values);

        return $stmt;
    }
}
