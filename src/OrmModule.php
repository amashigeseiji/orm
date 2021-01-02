<?php

namespace tenjuu99\ORM;

use Aura\SqlQuery\QueryFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Ray\Di\AbstractModule;

class OrmModule extends AbstractModule
{
    /** @var string */
    private $dbType;

    public function __construct(string $dbType)
    {
        $this->dbType = $dbType;
    }

    protected function configure() : void
    {
        $this->bind(DbConnectionInterface::class)->to(DbConnection::class);
        $this->bind(AnnotationReader::class);
        $this->bind(EntityFactory::class);
        $this->bind(QueryFactory::class)->toConstructor(
            QueryFactory::class,
            'db=queryfactory_dbtype'
        );
        $this->bind()->annotatedWith('queryfactory_dbtype')->toInstance($this->dbType);
    }
}
