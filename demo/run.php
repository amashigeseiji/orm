<?php
require __DIR__ . '/../vendor/autoload.php';

use Aura\Sql\ExtendedPdoInterface;
use Ray\Di\Di\Inject;
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

    private $pdoExtra;

    /**
     * @Inject
     */
    public function injectPdo(PDO $pdo) : void
    {
        $this->pdoExtra = $pdo;
    }

    public function createTable()
    {
        $this->pdoExtra->query('CREATE TABLE IF NOT EXISTS user(id integer, name varchar(255))')->execute();
    }

    public function createUser(int $id, string $name)
    {
        $stmt = $this->pdoExtra->prepare('INSERT INTO user(id, name) VALUES(:id, :name)');
        $stmt->execute(compact('id', 'name'));
    }
}

use tenjuu99\ORM\Annotation\Entity;
use tenjuu99\ORM\PdoModule;

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

$module = new OrmModule('sqlite');
$module->install(new PdoModule('sqlite:demo/demo.db'));
$injector = new Injector($module);
$demo = $injector->getInstance(Demo::class);
$demo->loop();
