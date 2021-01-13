<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once dirname(__DIR__) . '/demo/Table/Sample.php';

use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use Ray\Di\Injector;
use tenjuu99\ORM\OrmModule;
use tenjuu99\ORM\PdoModule;
use tenjuu99\ORM\TableModule;

$meta = new class() extends AbstractAppMeta {};
$meta->name = 'MyVendor\Demo';

$module = new ResourceModule('MyVendor\Demo');
$module->install(new OrmModule('sqlite'));
$module->install(new PdoModule('sqlite:demo/table-demo.db'));
$module->override(new TableModule($meta));
/** @var ResourceInterface */
$resource = (new Injector($module, __DIR__ . '/tmp'))->getInstance(ResourceInterface::class);
var_dump($resource->get('table://self/sample')->body);
