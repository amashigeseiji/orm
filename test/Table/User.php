<?php

namespace Vendor\Name\Resource\Table;

use BEAR\Resource\ResourceObject;

class User extends ResourceObject
{
    public function onGet() : ResourceObject
    {
        return $this;
    }
}
