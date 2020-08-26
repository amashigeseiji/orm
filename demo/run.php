<?php
require __DIR__ . '/../vendor/autoload.php';

use Aura\Sql\ExtendedPdoInterface;
use Ray\Di\Injector;
use tenjuu99\ORM\AbstractRepository;
use tenjuu99\ORM\OrmModule;

class Demo
{
    private $repository;
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function loop()
    {
        $this->repository->createTable();
        $this->repository->createUser(1, 'hoge');
        $this->repository->createUser(2, 'fuga');
        foreach ($this->repository as $row) {
            var_dump($row);
        }
        foreach ($this->repository->where('id', 1) as $user) {
            echo 'user id is: ' . $user->id . PHP_EOL;
            if ($user->id != 1) {
                throw new \Exception('id is not filtered.');
            }
        }
    }
}

class UserRepository extends AbstractRepository
{
    protected $from = 'user';

    public function createTable()
    {
        $this->getPdo()->query('CREATE TABLE IF NOT EXISTS user(id integer, name varchar(255))')->execute();
    }

    public function createUser(int $id, string $name)
    {
        $this->getPdo()->perform('INSERT INTO user(id, name) VALUES(:id, :name)', compact('id', 'name'));
    }

    private function getPdo() : ExtendedPdoInterface
    {
        /** @var ReflectionObject */
        $ref = new \ReflectionObject($this);
        $prop = $ref->getParentClass()->getProperty('pdo');
        $prop->setAccessible(true);
        /** @var ExtendedPdoInterface */
        return $prop->getValue($this);
    }
}

use tenjuu99\ORM\Annotation\Entity;

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

$module = new OrmModule('sqlite:demo/demo.db');
$injector = new Injector($module);
$demo = $injector->getInstance(Demo::class);
$demo->loop();
