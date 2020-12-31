<?php
namespace tenjuu99\ORM;

use Ray\Di\AbstractModule;
use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Resource\Module\SchemeCollectionProvider;
use BEAR\Resource\SchemeCollectionInterface;
use Doctrine\Common\Annotations\AnnotationReader;

class TableModule extends AbstractModule
{
    /** @var AbstractAppMeta */
    private $appMeta;

    public function __construct(AbstractAppMeta $abstractAppMeta)
    {
        $this->appMeta = $abstractAppMeta;
    }

    protected function configure() : void
    {
        $this->bind(TableAutoload::class);
        $this->bind()->annotatedWith('TableNamespace')->toInstance($this->appMeta->name . '\Tables');
        $this->bindInterceptor(
            $this->matcher->startsWith($this->appMeta->name . '\Resource\Table'),
            $this->matcher->any(),
            [TableInterceptor::class]
        );
        $this->bind(SchemeCollectionProvider::class);
        $this->bind(SchemeCollectionInterface::class)->toProvider(TableSchemeCollectionProvider::class);
    }
}
