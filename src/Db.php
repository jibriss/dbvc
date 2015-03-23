<?php
namespace Jibriss\Dbvc;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

class Db
{
    private $connection;
    /**
     * @var string
     */
    private $tableName;

    function __construct(Connection $connection, $tableName = 'migration_versions')
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function createMigrationsTableIfNotExists()
    {
        $sm = $this->connection->getSchemaManager();

        if (!$sm->tablesExist($this->tableName)) {
            $table = new Table($this->tableName);
            $table->addColumn('date', 'datetime');
            $table->addColumn('type', 'string');
            $table->addColumn('name', 'string');
            $table->addColumn('checksum', 'string');
            $table->addColumn('rollback', 'text', array('notnull' => false));
            $sm->createTable($table);
        }
    }

    public function getVersion($type, $name)
    {
        return $this->connection->fetchAssoc(
            "SELECT * FROM {$this->tableName} WHERE type = ? AND name = ?",
            array($type, $name)
        );
    }

    public function migrate($version)
    {
        $this->connection->beginTransaction();

        if (!empty($version["migration"])) {
            $this->connection->exec($version["migration"]);
        }

        $this->insert($version);
        $this->connection->commit();
    }

    public function rollback($version)
    {
        $this->connection->beginTransaction();

        if (!empty($version['rollback'])) {
            $this->connection->exec($version['rollback']);
        }

        $this->delete($version);
        $this->connection->commit();
    }

    public function getAllVersions($type)
    {
        return $this->connection->fetchAll(
            "SELECT * FROM {$this->tableName} WHERE type = ?",
            array($type)
        );
    }

    public function countPatches()
    {
        return $this->connection->fetchColumn("SELECT COUNT(*) FROM {$this->tableName} WHERE type = 'patch'");
    }

    public function getLastTag()
    {
        return $this->connection->fetchAssoc(
            "SELECT * FROM {$this->tableName} WHERE type = 'tag' ORDER BY name DESC LIMIT 1"
        );
    }

    public function insert($version)
    {
        $this->connection->insert(
            $this->tableName,
            array(
                'name'     => $version['name'],
                'type'     => $version['type'],
                'rollback' => $version['rollback'],
                'checksum' => md5($version['migration']),
                'date'     => date('Y-m-d H:i:s')
            )
        );
    }

    public function delete($version)
    {
        $this->connection->delete(
            $this->tableName,
            array('name' => $version['name'], 'type' => $version['type'])
        );
    }
}
