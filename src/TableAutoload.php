<?php
namespace tenjuu99\ORM;

use Ray\Di\Di\Named;

class TableAutoload
{
    private const TEMPLATE = <<<TEMPLATE
<?php
namespace {{NAMESPACE}};

use {{REPO_NAMESPACE}}\AbstractRepository;

class {{CLASSNAME}} extends AbstractRepository
{
    protected \$from = '{{TABLENAME}}';
    protected \$assign = Row\{{CLASSNAME}}::class;
}
TEMPLATE;

    /** @var string */
    private $namespace;

    /**
     * @Named("TableNamespace")
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function autoload(string $className) : void
    {
        if (strpos($className, $this->namespace . '\\') === 0) {
            if (strpos($className, $this->namespace . '\Row\\') === 0) {
                return;
            }
            $table = str_replace([$this->namespace . '\\', '.php'], ['', ''], $className);
            $newFile = str_replace(
                ['{{NAMESPACE}}', '{{REPO_NAMESPACE}}', '{{CLASSNAME}}', '{{TABLENAME}}'],
                [$this->namespace, __NAMESPACE__, $table, strtolower($table)],
                self::TEMPLATE
            );
            $path = sys_get_temp_dir() . '/' . $table . '.php';
            file_put_contents($path, $newFile);
            require_once $path;
        }
    }
}
