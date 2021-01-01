<?php
namespace tenjuu99\ORM;

use Aura\Sql\ExtendedPdoInterface;
use Ray\Di\Di\Named;

class TableSchema
{
    private const QUERY = [
        'mysql' => 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = (SELECT database())',
        'sqlite' => 'SELECT * FROM sqlite_master',
    ];

    /** @var DbConnectionInterface */
    private $conn;
    /** @var string */
    private $dbType;

    /** @var array */
    private $columns;
    /** @var array */
    private $allSchema;

    /**
     * @Named("dbType=queryfactory_dbtype")
     */
    public function __construct(DbConnectionInterface $conn, string $dbType)
    {
        $this->conn = $conn;
        $this->dbType = $dbType;
    }

    public function getColumns(string $tableName) : array
    {
        if (isset($this->columns[$tableName])) {
            return $this->columns[$tableName];
        }
        $method = 'columns' . ucfirst($this->dbType);
        $this->columns = $this->{$method}();
        return $this->columns[$tableName];
    }

    private function getAllSchema() : array
    {
        if ($this->allSchema) {
            return $this->allSchema;
        }
        if (!array_key_exists($this->dbType, self::QUERY)) {
            throw new \LogicException();
        }
        return $this->allSchema = $this->conn->query(self::QUERY[$this->dbType])->fetchAll();
    }

    private function columnsMysql()
    {
        $columns = [];
        $allSchema = $this->getAllSchema();
        foreach ($allSchema as $row) {
            $tableNameRow = $row['TABLE_NAME'];
            if (!isset($columns[$tableNameRow])) {
                $columns[$tableNameRow] = [];
            }
            $columns[$tableNameRow][] = [
                'name' => $row['COLUMN_NAME'],
                'type' => $row['COLUMN_TYPE'],
            ];
        }
        return $columns;
    }

    private function columnsSqlite()
    {
        $columns = [];
        $allSchema = $this->getAllSchema();
        foreach ($allSchema as $row) {
            $table = $row['tbl_name'];
            $columns[$table] = [];
            $string = strtolower($row['sql']);
            $pattern = "/create table {$table}\s?\((.*)\)/";
            preg_match($pattern, $string, $match);
            $tableColumns = explode(',', $match[1]);

            foreach ($tableColumns as $column) {
                $column = explode(' ', trim($column));
                $columns[$table][] = [
                    'name' => $column[0],
                    'type' => $column[1],
                ];
            }
        }
        return $columns;
    }
}
