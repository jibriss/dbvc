<?php
namespace Jibriss\Dbvc\Test;

use Jibriss\Dbvc\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    /** @var File */
    private $file;
    private $tmpPatch;
    private $tmpTag;

    public function setUp()
    {
        $this->tmpPatch = sys_get_temp_dir() . '/patch-' . uniqid() . '/';
        $this->tmpTag = sys_get_temp_dir() . '/tag-' . uniqid() .'/';
        mkdir($this->tmpPatch);
        mkdir($this->tmpTag);

        $this->file = new File($this->tmpPatch, $this->tmpTag);
    }

    public function tearDown()
    {
        foreach (array_merge(glob($this->tmpPatch . '*'), glob($this->tmpTag . '*')) as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpPatch);
        @rmdir($this->tmpTag);
    }

    public function testGetVersion()
    {
        file_put_contents($this->tmpPatch . 'feature-migration.sql', 'CREATE TABLE my_table(id INT);');
        file_put_contents($this->tmpPatch . 'feature-rollback.sql', 'DROP TABLE my_table;');

        $this->assertEquals(
            array(
                'type'      => 'patch',
                'name'      => 'feature',
                'migration' => 'CREATE TABLE my_table(id INT);',
                'rollback'  => 'DROP TABLE my_table;',
                'checksum'  => md5('CREATE TABLE my_table(id INT);'),
                'on_disk'   => true
            ),
            $this->file->getVersion('patch', 'feature')
        );

        $this->assertEquals(
            array(
                'type'      => 'patch',
                'name'      => 'another-feature',
                'on_disk'   => false
            ),
            $this->file->getVersion('patch', 'another-feature')
        );
    }

    public function testCreate()
    {
        $version = array(
            'type' => 'tag',
            'name' => '10',
            'migration' => 'CREATE TABLE my_table(id INT);',
            'rollback' => 'DROP TABLE my_table;'
        );

        $this->file->create($version);

        $this->assertStringEqualsFile($this->tmpTag . '10-migration.sql', 'CREATE TABLE my_table(id INT);');
        $this->assertStringEqualsFile($this->tmpTag . '10-rollback.sql', 'DROP TABLE my_table;');
    }

    public function testRemove()
    {
        file_put_contents($this->tmpTag . '1-migration.sql', 'CREATE TABLE tag_table(id INT);');
        file_put_contents($this->tmpTag . '1-rollback.sql', 'DROP TABLE tag_table;');
        file_put_contents($this->tmpPatch . 'coucou-migration.sql', 'CREATE TABLE my_table(id INT);');
        file_put_contents($this->tmpPatch . 'coucou-rollback.sql', 'DROP TABLE my_table;');

        $version = array(
            'type' => 'patch',
            'name' => 'coucou'
        );

        $this->file->remove($version);

        $this->assertFileNotExists($this->tmpPatch . 'coucou-migration.sql');
        $this->assertFileNotExists($this->tmpPatch . 'coucou-rollback.sql');
        $this->assertFileExists($this->tmpTag . '1-migration.sql');
        $this->assertFileExists($this->tmpTag . '1-rollback.sql');
    }

    public function testGetAllVersion()
    {
        file_put_contents($this->tmpPatch . 'patch-a-migration.sql', 'CREATE TABLE patch_a(id INT);');
        file_put_contents($this->tmpPatch . 'patch-a-rollback.sql', 'DROP TABLE patch_a;');
        file_put_contents($this->tmpPatch . 'patch-b-migration.sql', 'CREATE TABLE patch_b(id INT);');
        file_put_contents($this->tmpPatch . 'patch-b-rollback.sql', 'DROP TABLE patch_b;');
        // Theses files are in "tag" directory, they musn't affect the patch list
        file_put_contents($this->tmpTag . '1-migration.sql', 'CREATE TABLE tag_table(id INT);');
        file_put_contents($this->tmpTag . '1-rollback.sql', 'DROP TABLE tag_table;');

        $this->assertEquals(
            array(
                'patch-a' => array(
                    'type' => 'patch',
                    'name' => 'patch-a',
                    'migration' => 'CREATE TABLE patch_a(id INT);',
                    'rollback' => 'DROP TABLE patch_a;',
                    'checksum' => md5('CREATE TABLE patch_a(id INT);')
                ),
                'patch-b' => array(
                    'type' => 'patch',
                    'name' => 'patch-b',
                    'migration' => 'CREATE TABLE patch_b(id INT);',
                    'rollback' => 'DROP TABLE patch_b;',
                    'checksum' => md5('CREATE TABLE patch_b(id INT);')
                ),
            ),
            $this->file->getAllVersions('patch')
        );
    }

    public function testGetAllVersion_WhenDirectoryEmpty()
    {
        $this->assertEmpty($this->file->getAllVersions('patch'));

        // Theses files are in "tag" directory, they musn't affect the patch list
        file_put_contents($this->tmpTag . '1-migration.sql', 'CREATE TABLE super_relation(id INT);');
        file_put_contents($this->tmpTag . '1-rollback.sql', 'DROP TABLE super_relation;');

        $this->assertEmpty($this->file->getAllVersions('patch'));
    }

    public function testGetAllVersion_WhenRollbackScriptIsMissing_IgnoreMigrationFile()
    {
        file_put_contents($this->tmpTag . '1-migration.sql', 'CREATE TABLE super_relation(id INT);');

        $this->assertEmpty($this->file->getAllVersions('tag'));
    }

    /**
     * The tests on File#detectErrors() method only assert that the error message and exceptions messages contain
     * some keywords, like the name of missing files.
     * This will allow us to change error messages without breaking tests
     */
    public function testDetectErrors()
    {
        $this->assertEmpty($this->file->detectErrors());

        file_put_contents($this->tmpPatch . 'patch-a-migration.sql', 'CREATE TABLE patch_a(id INT);');
        file_put_contents($this->tmpPatch . 'patch-a-rollback.sql', 'DROP TABLE patch_a;');
        file_put_contents($this->tmpPatch . 'patch-b-migration.sql', 'CREATE TABLE patch_b(id INT);');
        file_put_contents($this->tmpPatch . 'patch-b-rollback.sql', 'DROP TABLE patch_b;');
        file_put_contents($this->tmpTag . '1-migration.sql', 'ALTER TABLE users ADD optin BOOLEAN;');
        file_put_contents($this->tmpTag . '1-rollback.sql', 'ALTER TABLE users DROP optin;');

        $this->assertEmpty($this->file->detectErrors());
    }

    public function testDetectErrors_WhenPatchRollbackIsMissing_ReturnError()
    {
        file_put_contents($this->tmpPatch . 'patch-a-migration.sql', 'CREATE TABLE patch_a(id INT);');

        $errors = $this->file->detectErrors();
        $this->assertCount(1, $errors);
        $this->assertContains($this->tmpPatch . 'patch-a-migration.sql', $errors[0]);
        $this->assertContains($this->tmpPatch . 'patch-a-rollback.sql', $errors[0]);
    }

    public function testDetectErrors_WhenPatchMigrationIsMissing_ReturnError()
    {
        file_put_contents($this->tmpPatch . 'patch-a-rollback.sql', 'DROP Table patch_a;');

        $errors = $this->file->detectErrors();
        $this->assertCount(1, $errors);
        $this->assertContains($this->tmpPatch . 'patch-a-rollback.sql', $errors[0]);
        $this->assertContains($this->tmpPatch . 'patch-a-migration.sql', $errors[0]);
    }

    public function testDetectErrors_WhenTagMigrationIsMissing_ThrowException()
    {
        file_put_contents($this->tmpTag . '2-rollback.sql', 'DROP TABLE tag;');

        $this->setExpectedException('RuntimeException', $this->tmpTag . '2-migration.sql');

        $this->file->detectErrors();
    }

    public function testDetectErrors_WhenTagRollbackIsMissing_ThrowException()
    {
        file_put_contents($this->tmpTag . '1-migration.sql', 'CREATE TABLE tag(id INT);');

        $this->setExpectedException('RuntimeException', $this->tmpTag . '1-rollback.sql');

        $this->file->detectErrors();
    }

    public function testDetectErrors_WhenIncorrectFileName()
    {
        file_put_contents($this->tmpPatch . 'bad-name.txt', '');
        file_put_contents($this->tmpTag . 'another-file-with-incorrect-name.exe', '');

        $errors = $this->file->detectErrors();

        $this->assertCount(2, $errors);
        $this->assertContains($this->tmpTag . 'another-file-with-incorrect-name.exe', $errors[0]);
        $this->assertContains($this->tmpPatch . 'bad-name.txt', $errors[1]);
    }
}
