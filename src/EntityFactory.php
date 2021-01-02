<?php

namespace tenjuu99\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionObject;
use tenjuu99\ORM\Annotation\Entity;

class EntityFactory
{
    /** @var AnnotationReader */
    private $reader;
    /** @var null|ReflectionClass */
    private $class;

    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
    }

    public function setClass(?string $class) : void
    {
        if (null === $class) {
            $this->class = null;
        } elseif (class_exists($class)) {
            $this->class = new ReflectionClass($class);
        }
    }

    public function ready() : bool
    {
        return null !== $this->class;
    }

    /**
     * @param array|bool $item
     *
     * @return mixed
     */
    public function create($item)
    {
        if (! $this->ready()) {
            return $item;
        }
        if (! is_array($item)) {
            return $item;
        }
        $obj = $this->class->newInstanceWithoutConstructor();
        $ref = new ReflectionObject($obj);
        foreach ($ref->getProperties() as $prop) {
            $annotation = $this->reader->getPropertyAnnotation($prop, Entity::class);
            if (! $annotation instanceof Entity) {
                continue;
            }
            $key = $annotation->getName();
            $prop->setAccessible(true);
            $prop->setValue($obj, $item[$key] ?: null);
        }

        return $obj;
    }
}
