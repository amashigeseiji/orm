<?php

namespace tenjuu99\Test;

require_once __DIR__ . '/Table/User.php';

use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use Koriym\HttpConstants\StatusCode;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use tenjuu99\ORM\OrmModule;
use tenjuu99\ORM\PdoModule;
use tenjuu99\ORM\TableModule;

/**
 * @internal
 * @coversNothing
 */
class TableResourceTest extends TestCase
{
    /** @var Injector */
    private static $injector;

    public static function setUpBeforeClass() : void
    {
        $meta = new class() extends AbstractAppMeta {};
        $meta->name = 'Vendor\Name';
        $module = new ResourceModule($meta->name);
        $module->install(new OrmModule('sqlite'));
        $module->install(new PdoModule('sqlite:test.db', '', '', [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $module->override(new TableModule($meta));
        self::$injector = new Injector($module, sys_get_temp_dir());

        /** @var PDO */
        $pdo = self::$injector->getInstance(PDO::class);
        $pdo->exec('CREATE TABLE user(id integer, name varchar(255))');
        $pdo->exec('INSERT INTO user values(1, "test1 user"), (2, "test2 user"), (3, "test3 user")');
    }

    public static function tearDownAfterClass() : void
    {
        unlink('test.db');
    }

    public function testTableResourceTest()
    {
        /** @var ResourceInterface */
        $resource = self::$injector->getInstance(ResourceInterface::class);
        $ro = $resource->get('table://self/user');
        $this->assertSame(StatusCode::OK, $ro->code);
        $this->assertArrayHasKey('user', $ro->body);
        $jsonDecoded = json_decode($ro, true);
        $this->assertEquals(
            $jsonDecoded['user'][0],
            [
                'user__id' => 1,
                'user__name' => 'test1 user',
            ]
        );
        $this->assertEquals(
            $jsonDecoded['user'][1],
            [
                'user__id' => 2,
                'user__name' => 'test2 user',
            ]
        );
        $this->assertEquals(
            $jsonDecoded['user'][2],
            [
                'user__id' => 3,
                'user__name' => 'test3 user',
            ]
        );
    }
}
