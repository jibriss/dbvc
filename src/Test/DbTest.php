<?php
namespace Jibriss\Dbvc\Test;

use Doctrine\DBAL\DriverManager;
use Jibriss\Dbvc\Db;

class DbTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \Doctrine\DBAL\Connection */
    private $connection;
    /** @var  Db */
    private $db;

    public function setUp()
    {
        $this->connection = DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'memory' => true));
        $this->db = new Db($this->connection);
        $this->db->createMigrationsTableIfNotExists();
    }

    public function testCountPatches()
    {
        $this->assertEquals(0, $this->db->countPatches());

        $this->connection->insert('migration_versions', array('type' => 'patch', 'name' => '', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s')));
        $this->assertEquals(1, $this->db->countPatches());

        $this->connection->insert('migration_versions', array('type' => 'patch', 'name' => '', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s')));
        $this->assertEquals(2, $this->db->countPatches());

        $this->connection->insert('migration_versions', array('type' => 'tag', 'name' => '', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s')));
        $this->assertEquals(2, $this->db->countPatches());
    }

    public function testGetLastTag()
    {
        $this->assertEquals(false, $this->db->getLastTag());

        $tag1 = array('type' => 'tag', 'name' => '1', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s'));
        $this->connection->insert('migration_versions', $tag1);
        $this->assertEquals($tag1, $this->db->getLastTag());

        $patch = array('type' => 'patch', 'name' => 'a-patch-to-ignore', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s'));
        $this->connection->insert('migration_versions', $patch);
        $this->assertEquals($tag1, $this->db->getLastTag());

        $tag2 = array('type' => 'tag', 'name' => '2', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s'));
        $this->connection->insert('migration_versions', $tag2);
        $this->assertEquals($tag2, $this->db->getLastTag());

        $patch = array('type' => 'patch', 'name' => '3', 'checksum' => '', 'rollback' => '', 'date' => date('Y-m-d H:i:s'));
        $this->connection->insert('migration_versions', $patch);
        $this->assertEquals($tag2, $this->db->getLastTag());
    }

    public function testMigrate()
    {
        $tag = array(
            'name'      => '1',
            'type'      => 'tag',
            'migration' => 'CREATE TABLE test_users(id INT);',
            'rollback'  => 'DROP TABLE test_users;'
        );

        $this->db->migrate($tag);

        $this->assertEquals(
            array(
                array(
                    'name'      => '1',
                    'type'      => 'tag',
                    'rollback'  => 'DROP TABLE test_users;',
                    'date'      => date('Y-m-d H:i:s'),
                    'checksum'  => md5('CREATE TABLE test_users(id INT);')
                )
            ),
            $this->connection->fetchAll('SELECT * FROM migration_versions')
        );
        $this->assertNotFalse(
            $this->connection->fetchAll('SELECT id FROM test_users;'),
            'Le script de migration a bien été créé'
        );
    }

    public function testRollback()
    {
        $patch = array(
            'name'     => 'my_patch',
            'type'     => 'patch',
            'rollback' => 'DROP TABLE test_articles;',
            'checksum' => '',
            'date'     => date('Y-m-d H:i:s')
        );

        $this->connection->executeUpdate('CREATE TABLE test_articles(id INT, content TEXT)');
        $this->connection->insert('migration_versions', $patch);

        $this->db->rollback($patch);

        $this->assertEquals(0, $this->connection->fetchColumn('SELECT COUNT(*) FROM migration_versions'));
        $this->setExpectedException('Doctrine\DBAL\DBALException', 'no such table');
        $this->connection->fetchAll('SELECT * FROM test_articles');
    }
}
