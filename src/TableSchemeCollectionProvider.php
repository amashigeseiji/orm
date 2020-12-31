<?php
namespace tenjuu99\ORM;

use BEAR\Resource\Annotation\AppName;
use BEAR\Resource\AppAdapter;
use BEAR\Resource\Module\SchemeCollectionProvider;
use Ray\Di\InjectorInterface;
use Ray\Di\ProviderInterface;

class TableSchemeCollectionProvider
{
    /**
     * @var string
     */
    private $appName;

    /**
     * @var InjectorInterface
     */
    private $injector;

    /**
     * @var SchemeCollectionProvider
     */
    private $provider;

    /**
     * @param string $appName
     *
     * @AppName("appName")
     */
    public function __construct(string $appName, InjectorInterface $injector, SchemeCollectionProvider $provider)
    {
        $this->appName = $appName;
        $this->injector = $injector;
        $this->provider = $provider;
    }

    /**
     * @return \BEAR\Resource\SchemeCollection
     */
    public function get()
    {
        $collection = $this->provider->get();
        $adapter = new AppAdapter($this->injector, $this->appName);
        $collection->scheme('table')->host('self')->toAdapter($adapter);

        return $collection;
    }
}
