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

    public function getAllVersions($type)
    {
        $versions = array();

        foreach (glob($this->path[$type] . '*-migration.sql') as $file) {
            $name = str_replace(array($this->path[$type], '-migration.sql'), '', $file);
            $migration = file_get_contents($file);
            $rollbackFile = str_replace('-migration.sql', '-rollback.sql', $file);

            $versions[$name] = array(
                'name'      => $name,
                'migration' => $migration,
                'rollback'  => file_exists($rollbackFile) ? file_get_contents($rollbackFile) : '',
                'checksum'  => md5($migration)
            );
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
            $checksum = md5($migrationFile);
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
}
