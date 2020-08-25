<?php
namespace tenjuu99\ORM;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use Aura\SqlQuery\QueryFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class OrmModule extends AbstractModule
{
    /** @var string */
    private $dsn;
    /** @var ?string */
    private $username;
    /** @var ?string */
    private $password;
    /** @var ?array */
    private $options;
    /** @var ?array */
    private $queries;
    /** @var string */
    private $dbType;

    public function __construct(string $dsn, string $username = '', string $password = '', array $options = [], array $queries = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->queries = $queries;
        $dbType = explode(':', $dsn)[0];
        $this->dbType = $dbType;
    }

    protected function configure() : void
    {
        $this->bind(ExtendedPdoInterface::class)->toConstructor(
            ExtendedPdo::class,
            'dsn=pdo_dsn,username=pdo_username,password=pdo_password,options=pdo_options,queries=pdo_queries'
        )->in(Scope::SINGLETON);

        $this->bind()->annotatedWith('pdo_dsn')->toInstance($this->dsn);
        $this->bind()->annotatedWith('pdo_username')->toInstance($this->username);
        $this->bind()->annotatedWith('pdo_password')->toInstance($this->password);
        $this->bind()->annotatedWith('pdo_options')->toInstance($this->options);
        $this->bind()->annotatedWith('pdo_queries')->toInstance($this->options);
        $this->bind(AnnotationReader::class);
        $this->bind(EntityFactory::class);
        $this->bind(QueryFactory::class)->toConstructor(
            QueryFactory::class,
            'db=queryfactory_dbtype'
        );
        $this->bind()->annotatedWith('queryfactory_dbtype')->toInstance($this->dbType);
    }
}
