<?php
namespace tenjuu99\Test;

use PDO;
use PHPUnit\Framework\TestCase;
use tenjuu99\ORM\DbConnection;

class DbConnectionTest extends TestCase
{
    public function testQuery()
    {
        if (file_exists('test.db')) {
            unlink('test.db');
        }
        $pdo = new PDO('sqlite:test.db');
        $conn = new DbConnection($pdo);
        $conn->query('CREATE TABLE hoge(id int, name varchar(255))');
        $conn->query('INSERT INTO hoge(id, name) VALUES(1, "hoge")');
        $res = $conn->query('SELECT * FROM hoge')->fetch();
        $this->assertEquals(['1', 'hoge', 'id' => '1', 'name' => 'hoge'], $res);
    }

    public function testPerform()
    {
        if (file_exists('test.db')) {
            unlink('test.db');
        }
        $pdo = new PDO('sqlite:test.db');
        $conn = new DbConnection($pdo);
        $conn->query('CREATE TABLE hoge(id int, name varchar(255))');
        $conn->query('INSERT INTO hoge(id, name) VALUES(1, "hoge")');
        $this->assertEquals(
            ['1', 'hoge', 'id' => '1', 'name' => 'hoge'],
            $conn->perform('SELECT * FROM hoge WHERE id = ?', [1])->fetch()
        );
        $this->assertEquals(
            ['1', 'hoge', 'id' => '1', 'name' => 'hoge'],
            $conn->perform('SELECT * FROM hoge WHERE id = :id', ['id' => 1])->fetch()
        );
    }
}
