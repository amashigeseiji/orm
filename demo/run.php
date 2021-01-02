<?php
require __DIR__ . '/../vendor/autoload.php';

use Ray\Di\Di\Inject;
use Ray\Di\Di\PostConstruct;
use Ray\Di\Injector;
use tenjuu99\ORM\AbstractRepository;
use tenjuu99\ORM\OrmModule;

class Demo
{
    private $repository;
    private $db;
    public function __construct(UserRepository $repository, PDO $pdo)
    {
        $this->repository = $repository;
        $this->db = new class($pdo) {
            private $pdo;
            public function __construct(PDO $pdo)
            {
                $this->pdo = $pdo;
            }

            public function createTable() : void
            {
                $this->pdo->query('CREATE TABLE IF NOT EXISTS user(id integer, name varchar(255))')->execute();
                $this->pdo->query('CREATE TABLE IF NOT EXISTS project(id integer, name varchar(255), user_id integer)')->execute();
            }

            public function createUser(int $id, string $name) : void
            {
                $stmt = $this->pdo->prepare('INSERT INTO user(id, name) VALUES(:id, :name)');
                $stmt->execute(compact('id', 'name'));
            }

            public function createProject(string $name, int $user_id) : void
            {
                $stmt = $this->pdo->prepare('INSERT INTO project(name, user_id) VALUES(:name, :user_id)');
                $stmt->execute(compact('name', 'user_id'));
            }
        };
    }

    public function loop()
    {
        $this->db->createTable();
        $this->db->createUser(1, 'hoge');
        $this->db->createUser(2, 'fuga');
        $this->db->createProject('hoge\'s project 1', 1);
        $this->db->createProject('hoge\'s project 2', 1);
        foreach ($this->repository as $row) {
            var_dump($row);
        }
        foreach ($this->repository->where('user.id', 1) as $user) {
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
    protected $assign = User::class;

    /**
     * @PostConstruct
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->join('project', 'project.user_id = user.id');
        $this->with('project');
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

    /**
     * @Entity(prop="name",table="project")
     */
    public $projectName;
}

$db = 'demo/demo.db';
if (file_exists($db)) {
    unlink($db);
}
$module = new OrmModule('sqlite');
$module->install(new PdoModule('sqlite:demo/demo.db'));
$injector = new Injector($module);
$demo = $injector->getInstance(Demo::class);
$demo->loop();
