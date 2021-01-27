<?php
namespace tenjuu99\ORM;

use BEAR\Resource\ResourceObject;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Bind;
use Ray\Di\Container;
use Ray\Di\Di\Named;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use ReflectionClass;
use ReflectionProperty;

class TableInterceptor implements MethodInterceptor
{
    /** @var InjectorInterface */
    private $injector;

    /** @var TableAutoload */
    private $autoload;

    /** @var string */
    private $namespace;

    /**
     * @Named("namespace=TableNamespace")
     */
    public function __construct(
        InjectorInterface $injector,
        TableAutoload $autoload,
        string $namespace
    ) {
        $this->injector = $injector;
        $this->autoload = $autoload;
        $this->namespace = $namespace;
        spl_autoload_register([$this->autoload, 'autoload']);
    }

    public function invoke(MethodInvocation $invocation)
    {
        $ro = $invocation->getThis();
        assert($ro instanceof ResourceObject);
        $className = $this->getTableClassName($ro);
        $table = $this->getTableInstance($className);
        $prop = new ReflectionProperty($table, 'from');
        $prop->setAccessible(true);
        $ro->body[$prop->getValue($table)] = $table;

        return $invocation->proceed();
    }

    private function getTableClassName(ResourceObject $ro) : string
    {
        $ref = new ReflectionClass($ro);
        $shortName = $ref->getShortName();
        if (strpos($shortName, '_') !== false) {
            $shortName = explode('_', $shortName)[0];
        }

        return $this->namespace . '\\' . $shortName;
    }

    private function getTableInstance(string $className) : AbstractRepository
    {
        try {
            $table = $this->injector->getInstance($className);
        } catch (Unbound $unbound) {
            new Bind($this->injector->getInstance(Container::class), $className);
            $table = $this->injector->getInstance($className);
        }

        return $table;
    }
}
