<?php

namespace MyVendor\Demo\Resource\Table;

use BEAR\Resource\ResourceObject;

class Sample extends ResourceObject
{
    public function onGet() : ResourceObject
    {
        return $this;
    }
}
