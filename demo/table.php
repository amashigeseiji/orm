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

$module = new ResourceModule($meta->name);
$module->install(new OrmModule('sqlite'));
$module->install(new PdoModule('sqlite:demo/table-demo.db'));
$module->override(new TableModule($meta));
$injector = new Injector($module, __DIR__ . '/tmp');
/** @var PDO */
$pdo = $injector->getInstance(PDO::class);
$pdo->query('CREATE TABLE IF NOT EXISTS sample(id integer, name varchar(255))')->execute();
$pdo->query('INSERT INTO sample(id, name) VALUES (1, "test 1")')->execute();
$pdo->query('INSERT INTO sample(id, name) VALUES (2, "test 2")')->execute();
/** @var ResourceInterface */
$resource = $injector->getInstance(ResourceInterface::class);
//var_dump($resource->get('table://self/sample')->body['sample']);

foreach ($resource->get('table://self/sample')->body['sample'] as $row) {
    var_dump($row);
}
unlink('demo/table-demo.db');
