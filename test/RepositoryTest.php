<?php
namespace tenjuu99\Test;

require dirname(__DIR__) . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use tenjuu99\ORM\AbstractRepository;
use tenjuu99\ORM\Annotation\Entity;
use tenjuu99\ORM\OrmModule;

class RepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $pdo = new \PDO('sqlite:test.db');
        $pdo->exec('CREATE TABLE user(id integer, name varchar(255))');
        $pdo->exec('INSERT INTO user values(1, "test1 user"), (2, "test2 user")');
    }

    public static function tearDownAfterClass(): void
    {
        $pdo = new \PDO('sqlite:test.db');
        $pdo->exec('DROP TABLE user');
    }

    public function testRepository()
    {
        $module = new OrmModule('sqlite:test.db');
        $injector = new Injector($module);
        $repo = $injector->getInstance(UserRepository::class);
        self::assertCount(2, $repo);
        foreach ($repo as $row) {
            if ($row->id == 1) {
                $user = new User();
                $user->id = 1;
                $user->name = 'test1 user';
                self::assertEquals($user, $row);
            }
            if ($row->id == 2) {
                $user = new User();
                $user->id = 2;
                $user->name = 'test2 user';
                self::assertEquals($user, $row);
            }
        }
    }
}

class UserRepository extends AbstractRepository
{
    protected $from = 'user';
}

class User
{
    /**
     * @Entity(prop="id",table="user")
     */
    public $id;

    /**
     * @Entity(prop="name",table="user")
     */
    public $name;
}
