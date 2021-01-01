<?php
namespace tenjuu99\Test;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use tenjuu99\ORM\DbConnectionInterface;
use tenjuu99\ORM\TableSchema;

class TableSchemaTest extends TestCase
{
    private static $conn;

    public static function setUpBeforeClass(): void
    {
        $pdo = new PDO('sqlite:test.db');
        $pdo->exec('CREATE TABLE user(id integer primary key, name varchar(255))');
        $pdo->exec('CREATE TABLE project(id integer primary key, name varchar(255), user_id integer, foreign key(user_id) references user(id))');

        self::$conn = new class($pdo) implements DbConnectionInterface {
            /** @var PDO */
            private $pdo;
            public function __construct(PDO $pdo)
            {
                $this->pdo = $pdo;
            }

            public function query($statement, ...$fetch): PDOStatement
            {
                return $this->pdo->query($statement);
            }

            public function perform($statement, array $values = []) : PDOStatement
            {
            }
        };
    }

    public static function tearDownAfterClass(): void
    {
        $pdo = new PDO('sqlite:test.db');
        $pdo->exec('DROP TABLE user');
        $pdo->exec('DROP TABLE project');
    }

    /**
     * @test
     */
    public function testGetColumns()
    {
        $schema = new TableSchema(self::$conn, 'sqlite');
        $this->assertSame([
            [
                'name' => 'id',
                'type' => 'integer',
            ],
            [
                'name' => 'name',
                'type' => 'varchar(255)',
            ],
        ], $schema->getColumns('user'));
        $this->assertSame([
            [
                'name' => 'id',
                'type' => 'integer',
            ],
            [
                'name' => 'name',
                'type' => 'varchar(255)',
            ],
            [
                'name' => 'user_id',
                'type' => 'integer',
            ],
            [
                'name' => 'foreign',
                'type' => 'key(user_id)',
            ],
        ], $schema->getColumns('project'));
    }
}
