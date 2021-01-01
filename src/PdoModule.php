<?php
namespace tenjuu99\ORM;

use Ray\Di\AbstractModule;
use PDO;
use Ray\Di\Scope;

class PdoModule extends AbstractModule
{
    /** @var string */
    private $dsn;
    /** @var ?string */
    private $username;
    /** @var ?string */
    private $password;
    /** @var ?array */
    private $options;

    public function __construct(string $dsn, string $username = '', string $password = '', array $options = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    protected function configure() : void
    {
        $this->bind(PDO::class)->toConstructor(
            PDO::class,
            'dsn=pdo_dsn,username=pdo_username,password=pdo_password,options=pdo_options'
        )->in(Scope::SINGLETON);

        $this->bind()->annotatedWith('pdo_dsn')->toInstance($this->dsn);
        $this->bind()->annotatedWith('pdo_username')->toInstance($this->username);
        $this->bind()->annotatedWith('pdo_password')->toInstance($this->password);
        $this->bind()->annotatedWith('pdo_options')->toInstance($this->options);
    }
}
