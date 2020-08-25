<?php
namespace tenjuu99\ORM\Annotation;

/**
 * @Annotation
 */
final class Entity
{
    /** @var string */
    public $prop = '';
    /** @var string */
    public $table = '';

    public function getName() : string
    {
        if ($this->table) {
            return $this->table . '__' . $this->prop;
        }

        return $this->prop;
    }
}
