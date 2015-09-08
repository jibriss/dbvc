<?php
namespace Jibriss\Dbvc;

class File
{
    private $path;

    public function __construct($patchesPath, $tagsPath)
    {
        $this->path = array(
            'patch' => rtrim($patchesPath, '/') . '/',
            'tag'   => rtrim($tagsPath, '/') . '/'
        );
    }

    public function detectErrors()
    {
        $errors = array();

        foreach (glob($this->path['tag'] .'*') as $file) {
            if (substr($file, -14) !== '-migration.sql' && substr($file, -13) !== '-rollback.sql') {
                $errors[] = sprintf(
                    "The file '%s' does not follow the naming convention rule.\nIt will be ignored",
                    $file
                );
            } elseif (substr($file, -14) === '-migration.sql') {
                $rollbackFile = str_replace('-migration.sql', '-rollback.sql', $file);

                if (!file_exists($rollbackFile)) {
                    throw new \RuntimeException(
                        "The tags directory has been corrupted : the file '$rollbackFile' is missing.\n"
                        . "Please fix this manually"
                    );
                }
            } elseif (substr($file, -13) === '-rollback.sql') {
                $migrationFile = str_replace('-rollback.sql', '-migration.sql', $file);

                if (!file_exists($migrationFile)) {
                    throw new \RuntimeException(
                        "The tags directory has been corrupted : the file '$migrationFile' is missing.\n"
                        . "Please fix this manually"
                    );
                }
            }
        }

        foreach (glob($this->path['patch'] .'*') as $file) {
            if (substr($file, -14) !== '-migration.sql' && substr($file, -13) !== '-rollback.sql') {
                $errors[] = sprintf(
                    "The file '%s' does not follow the naming convention rule.\nIt will be ignored",
                    $file
                );
            } elseif (substr($file, -14) === '-migration.sql') {
                $rollbackFile = str_replace('-migration.sql', '-rollback.sql', $file);

                if (!file_exists($rollbackFile)) {
                    $errors[] = sprintf(
                        "The migration '%s' has no rollback file associated.\nIt should be named '%s'.\n"
                        . "If there is no rollback to do, please create an empty file.\nThis file will be ignored.",
                        $file, $rollbackFile
                    );
                }
            } elseif (substr($file, -13) === '-rollback.sql') {
                $migrationFile = str_replace('-rollback.sql', '-migration.sql', $file);

                if (!file_exists($migrationFile)) {
                    $errors[] = sprintf(
                        "The rollback '%s' has no migration file associated.\nIt should be named '%s'.\n"
                        . "If there is no migration to do, please create an empty file.\nThis file will be ignored.",
                        $file, $migrationFile
                    );
                }
            }
        }

        return $errors;
    }

    public function getAllVersions($type)
    {
        $versions = array();

        foreach (glob($this->path[$type] . '*-migration.sql') as $file) {
            $name = str_replace(array($this->path[$type], '-migration.sql'), '', $file);
            $rollbackFile = str_replace('-migration.sql', '-rollback.sql', $file);

            if (file_exists($rollbackFile)) {
                $migration = file_get_contents($file);
                $versions[$name] = array(
                    'name'      => $name,
                    'type'      => $type,
                    'migration' => $migration,
                    'rollback'  => file_exists($rollbackFile) ? file_get_contents($rollbackFile) : null,
                    'checksum'  => md5($migration)
                );
            }
        }

        return $versions;
    }

    public function getVersion($type, $name)
    {
        $migrationFile = $this->path[$type] . $name . '-migration.sql';
        $rollbackFile = $this->path[$type] . $name . '-rollback.sql';

        $version = array(
            'type' => $type,
            'name' => $name
        );

        if (file_exists($migrationFile)) {
            $migration = file_get_contents($migrationFile);
            $checksum = md5($migration);
            $version['rollback']  = file_exists($rollbackFile) ? file_get_contents($rollbackFile) : '';
            $version['migration'] = $migration;
            $version['checksum']  = $checksum;
            $version['on_disk']   = true;
        } else {
            $version['on_disk'] = false;
        }


        return $version;
    }

    public function create($version)
    {
        $migrationFile = $this->path[$version['type']] . $version['name'] . '-migration.sql';
        $rollbackFile = $this->path[$version['type']] . $version['name'] . '-rollback.sql';

        file_put_contents($migrationFile, $version['migration']);
        file_put_contents($rollbackFile, $version['rollback']);
    }

    public function remove($version)
    {
        $migrationFile = $this->path[$version['type']] . $version['name'] . '-migration.sql';
        $rollbackFile = $this->path[$version['type']] . $version['name'] . '-rollback.sql';

        file_exists($migrationFile) && unlink($migrationFile);
        file_exists($rollbackFile) && unlink($rollbackFile);
    }

    public function getMigrationFilePath($type, $name)
    {
        return $this->path[$type] . $name . '-migration.sql';
    }

    public function getRollbackFilePath($type, $name)
    {
        return $this->path[$type] . $name . '-rollback.sql';
    }

}
