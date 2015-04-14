<?php
namespace Jibriss\Dbvc\Test;

use Jibriss\Dbvc\Dbvc;

class DbvcTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $dbMock;
    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $fileMock;
    /** @var  Dbvc */
    private $dbvc;

    public function setUp()
    {
        $this->dbMock = $this->getMockBuilder('\Jibriss\Dbvc\Db')->disableOriginalConstructor()->getMock();
        $this->fileMock = $this->getMockBuilder('\Jibriss\Dbvc\File')->disableOriginalConstructor()->getMock();
        $this->dbvc = new Dbvc($this->fileMock, $this->dbMock);
    }

    public function testGetNextTagName()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getLastTag')
            ->will($this->returnValue(array('type' => 'tag', 'name' => 10)));

        $this->assertEquals(11, $this->dbvc->getNextTagName());
    }

    public function testGetNextTagName_WhenNoTagInDb()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getLastTag')
            ->will($this->returnValue(false));

        $this->assertEquals(1, $this->dbvc->getNextTagName());
    }

    public function testIsThereAnyPatchInDb_WhenNoPatcheInDb()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('countPatches')
            ->will($this->returnValue(0));

        $this->assertFalse($this->dbvc->isThereAnyPatchInDb());
    }

    public function testIsThereAnyPatchInDb()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('countPatches')
            ->will($this->returnValue(10));

        $this->assertTrue($this->dbvc->isThereAnyPatchInDb());
    }

    public function testGetStatus_Empty()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array()));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array()));

        $this->assertEquals(
            array(),
            $this->dbvc->getStatus('patch')
        );
    }

    public function testGetStatus_PatchOnlyOnDisk()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array()));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(
                array(
                    array('name' => 'patch-1', 'migration' => 'SQL SQL SQL', 'rollback' => 'LQS LQS LQS', 'checksum' => md5('SQL SQL SQL'))
                )
            ));

        $this->assertEquals(
            array(
                'patch-1' => array(
                    'type' => 'patch',
                    'on_disk' => true,
                    'in_db' => false,
                    'changed' => false,
                    'name' => 'patch-1',
                    'migration' => 'SQL SQL SQL',
                    'rollback' => 'LQS LQS LQS'
                )
            ),
            $this->dbvc->getStatus('patch')
        );
    }

    public function testGetStatus_PatchOnlyInDb()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array(
                array('name' => 'patch-1', 'rollback' => 'LQS LQS LQS', 'checksum' => md5('SQL SQL SQL'))
            )));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array()));

        $this->assertEquals(
            array(
                'patch-1' => array(
                    'type' => 'patch',
                    'on_disk' => false,
                    'in_db' => true,
                    'changed' => false,
                    'name' => 'patch-1',
                    'rollback' => 'LQS LQS LQS',
                    'checksum' => md5('SQL SQL SQL')
                )
            ),
            $this->dbvc->getStatus('patch')
        );
    }

    public function testGetStatus_TagInDbAndOnDisk()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('tag')
            ->will($this->returnValue(array(
                array('name' => '1', 'rollback' => 'DROP TABLE a;', 'checksum' => md5('CREATE TABLE a(id INT);')),
                array('name' => '2', 'rollback' => 'DROP TABLE b;', 'checksum' => md5('CREATE TABLE b(id INT);')),
            )));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('tag')
            ->will($this->returnValue(array(
                array('name' => '1', 'rollback' => 'DROP TABLE a;', 'migration' => 'CREATE TABLE a(id INT);', 'checksum' => md5('CREATE TABLE a(id INT);')),
                array('name' => '2', 'rollback' => 'DROP TABLE b;', 'migration' => 'CREATE TABLE b(id INT);', 'checksum' => md5('CREATE TABLE b(id INT);'))
            )));

        $this->assertEquals(
            array(
                '1' => array(
                    'type' => 'tag',
                    'on_disk' => true,
                    'in_db' => true,
                    'changed' => false,
                    'name' => '1',
                    'migration' => 'CREATE TABLE a(id INT);',
                    'rollback' => 'DROP TABLE a;',
                    'checksum' => md5('CREATE TABLE a(id INT);')
                ),
                '2' => array(
                    'type' => 'tag',
                    'on_disk' => true,
                    'in_db' => true,
                    'changed' => false,
                    'name' => '2',
                    'migration' => 'CREATE TABLE b(id INT);',
                    'rollback' => 'DROP TABLE b;',
                    'checksum' => md5('CREATE TABLE b(id INT);')
                )
            ),
            $this->dbvc->getStatus('tag')
        );
    }

    public function testGetStatus_TagOrder()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('tag')
            ->will($this->returnValue(array(
                        array('name' => '10', 'rollback' => 'DROP TABLE c;', 'checksum' => md5('CREATE TABLE c(id INT);')),
                        array('name' => '8', 'rollback' => 'DROP TABLE a;', 'checksum' => md5('CREATE TABLE a(id INT);')),
                        array('name' => '9', 'rollback' => 'DROP TABLE b;', 'checksum' => md5('CREATE TABLE b(id INT);')),
                    )));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('tag')
            ->will($this->returnValue(array(
                        array('name' => '8', 'rollback' => 'DROP TABLE a;', 'migration' => 'CREATE TABLE a(id INT);', 'checksum' => md5('CREATE TABLE a(id INT);')),
                        array('name' => '10', 'rollback' => 'DROP TABLE c;', 'migration' => 'CREATE TABLE c(id INT);', 'checksum' => md5('CREATE TABLE c(id INT);')),
                        array('name' => '9', 'rollback' => 'DROP TABLE b;', 'migration' => 'CREATE TABLE b(id INT);', 'checksum' => md5('CREATE TABLE b(id INT);')),
                    )));

        $this->assertEquals(
            array('8', '9', '10'),
            array_keys($this->dbvc->getStatus('tag'))
        );
    }

    public function testCreateNewTag()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getLastTag')
            ->will($this->returnValue(array('name' => '9')));
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array(
                array('name' => 'first-patch', 'rollback' => 'DROP TABLE a;', 'checksum' => md5('CREATE TABLE a(id INT);')),
                array('name' => 'second-patch', 'rollback' => 'DROP TABLE b;', 'checksum' => md5('CREATE TABLE b(id INT);')),
            )));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array(
                array('name' => 'first-patch', 'rollback' => 'DROP TABLE a;', 'migration' => 'CREATE TABLE a(id INT);', 'checksum' => md5('CREATE TABLE a(id INT);')),
                array('name' => 'second-patch', 'rollback' => 'DROP TABLE b;', 'migration' => 'CREATE TABLE b(id INT);', 'checksum' => md5('CREATE TABLE b(id INT);'))
            )));

        $this->dbMock
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function($tag) {
                return $tag['name'] == 10
                    && $tag['type'] == 'tag'
                    && strpos($tag['rollback'], 'DROP TABLE a;') !== false
                    && strpos($tag['rollback'], 'DROP TABLE b;') !== false;
            }));
        $this->fileMock
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function($tag) {
                return $tag['name'] == 10
                    && $tag['type'] == 'tag'
                    && strpos($tag['rollback'], 'DROP TABLE a;') !== false
                    && strpos($tag['rollback'], 'DROP TABLE b;') !== false
                    && strpos($tag['migration'], 'CREATE TABLE a(id INT);') !== false
                    && strpos($tag['migration'], 'CREATE TABLE b(id INT);') !== false;
            }));

        $this->assertArraySubset(
            array(
                'name' => '10',
                'type' => 'tag'
            ),
            $this->dbvc->createNewTag()
        );

    }

    public function testGetAllPatchesToRollback()
    {
        $this->dbMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array(
                array('name' => 'patch-a', 'rollback' => 'DROP TABLE a;', 'checksum' => md5('CREATE TABLE a(id INT);')),
                array('name' => 'patch-b', 'rollback' => 'DROP TABLE b;', 'checksum' => md5('CREATE TABLE b(id INT);')),
            )));
        $this->fileMock
            ->expects($this->once())
            ->method('getAllVersions')
            ->with('patch')
            ->will($this->returnValue(array(
                array('name' => 'patch-b', 'type' => 'patch', 'rollback' => 'DROP TABLE b;', 'migration' => 'CREATE TABLE b(id INT);', 'checksum' => md5('CREATE TABLE b(id INT);')),
            )));

        $this->assertEquals(
            array(
                'patch-a' => array(
                    'name'      => 'patch-a',
                    'type'      => 'patch',
                    'rollback'  => 'DROP TABLE a;',
                    'checksum'  => md5('CREATE TABLE a(id INT);'),
                    'on_disk'   => false,
                    'in_db'     => true,
                    'changed'   => false
                )
            ),
            $this->dbvc->getAllPatchesToRollback()
        );
    }

    public function testDetectErrors()
    {
        $this->fileMock
            ->expects($this->once())
            ->method('detectErrors')
            ->will($this->returnValue(array('error1', 'error2')));

        $this->assertEquals(array('error1', 'error2'), $this->dbvc->detectErrors());
    }

    /**
     * @dataProvider trueFalseProvider
     */
    public function testMigrate($withoutScript)
    {
        $this->dbMock
            ->expects($this->once())
            ->method('migrate')
            ->with('patch1', $withoutScript);

        $this->dbvc->migrate('patch1', $withoutScript);
    }

    /**
     * @dataProvider trueFalseProvider
     */
    public function testRollback($withoutScript)
    {
        $this->dbMock
            ->expects($this->once())
            ->method('rollback')
            ->with('tag2', $withoutScript);

        $this->dbvc->rollback('tag2', $withoutScript);
    }

    public function trueFalseProvider()
    {
        return array(
            array(true),
            array(false)
        );
    }
}
