<?php

namespace Jibriss\Dbvc;

use Exception;

class Dbvc
{
    /**
     * @var File
     */
    private $file;
    /**
     * @var Db
     */
    private $db;

    function __construct(File $file, Db $db)
    {
        $this->file = $file;
        $this->db = $db;
    }

    public function getStatus($type)
    {
        $versions = array();

        foreach ($this->db->getAllVersions($type) as $v) {
            $v['in_db'] = true;
            $v['on_disk'] = false;
            $v['changed'] = false;
            $v['type']    = $type;
            $versions[$v['name']] = $v;
        }

        foreach ($this->file->getAllVersions($type) as $v) {
            if (!isset($versions[$v['name']])) {
                $versions[$v['name']] = array(
                    'in_db'    => false,
                    'rollback' => $v['rollback'],
                    'name'     => $v['name'],
                    'type'     => $type
                );
            }

            $versions[$v['name']] = array_merge(
                $versions[$v['name']],
                array(
                    'migration' => $v['migration'],
                    'on_disk'   => true,
                    'changed'   => $versions[$v['name']]['in_db'] && ($versions[$v['name']]['checksum'] != md5($v['migration']))
                )
            );
        }

        if ($type == 'tag') {
            uasort(
                $versions,
                function($a, $b) {
                    return (int)$a['name'] - (int)$b['name'];
                }
            );
        }

        return $versions;
    }

    public function getVersion($type, $name)
    {
        $version = $this->db->getVersion($type, $name);

        if ($version === false) {
            $version = array(
                'name'  => $name,
                'type'  => $type,
                'in_db' => false
            );
        } else {
            $version['in_db'] = true;
        }

        $file = $this->file->getVersion($type, $name);

        if ($file['on_disk']) {
            $version['on_disk'] = true;
            $version['migration'] = $file['migration'];
            $version['rollback'] = isset($version['rollback']) ? $version['rollback'] : $file['rollback'];
            $version['changed'] = $version['in_db'] && $version['checksum'] != $file['checksum'];
        } else {
            $version['on_disk'] = false;
            $version['changed'] = false;
        }

        return $version;
    }

    public function migrate($version, $withoutScript = false)
    {
        $this->db->migrate($version, $withoutScript);
    }

    public function rollback($version, $withoutScript = false)
    {
        $this->db->rollback($version, $withoutScript);
    }

    public function isThereAnyPatchInDb()
    {
        return $this->db->countPatches() > 0;
    }

    public function getAllTagToMigrate()
    {
        $tags = $this->getStatus('tag');

        $tags = array_filter($tags, function($tag) {
            return !$tag['in_db'];
        });

        return $tags;
    }

    public function getAllTagToRollback($targetTagName)
    {
        $tags = $this->getStatus('tag');

        $tags = array_filter($tags, function($tag) use($targetTagName) {
            return $tag['in_db'] && (int)$tag['name'] > (int)$targetTagName;
        });

        $tags = array_reverse($tags);

        return $tags;
    }

    public function getAllPatchesThatChanged()
    {
        $patches = $this->getStatus('patch');

        $patches = array_filter($patches, function($patch) {
            return $patch['changed'];
        });


        return $patches;
    }

    public function getAllPatchesNotInDb()
    {
        $patches = $this->getStatus('patch');

        $patches = array_filter($patches, function($patch) {
            return !$patch['in_db'];
        });

        return $patches;
    }

    public function getAllPatchesInDb()
    {
        $patches = $this->getStatus('patch');

        $patches = array_filter($patches, function($patch) {
                return $patch['in_db'];
            });

        return $patches;
    }

    public function getAllPatchesToRollback()
    {
        $patches = $this->getStatus('patch');

        $patches = array_filter($patches, function($patch) {
            return $patch['in_db'] && !$patch['on_disk'];
        });

        return $patches;
    }

    public function getNextTagName()
    {
        $tag = $this->db->getLastTag();
        return $tag === false ? 1 : (int)$tag['name'] + 1;
    }

    public function createNewTag()
    {
        $patches = $this->getAllPatchesInDb();
        $migration = '';
        $rollback = '';

        foreach ($patches as $patch) {
            $migration .= "-- Patch '{$patch['name']}'\n" . $patch['migration'] . "\n\n";
            $rollback = "-- Patch '{$patch['name']}'\n" . $patch['rollback'] . "\n\n" . $rollback;
        }

        $tag = array(
            'name' => (string)$this->getNextTagName(),
            'type' => 'tag',
            'migration' => $migration,
            'rollback' => $rollback
        );

        $this->db->insert($tag);
        $this->file->create($tag);

        foreach ($patches as $patch) {
            $this->db->delete($patch);
            $this->file->remove($patch);
        }

        return $tag;
    }

    public function createNewPatch($name)
    {
        $version = $this->file->getVersion('patch', $name);

        if ($version['on_disk']) {
            throw new Exception("Patch '$name' already exists");
        }

        $patch = array(
            'name' => $name,
            'type' => FileType::PATCH,
            'migration' => '',
            'rollback' => ''
        );

        $this->file->create($patch);

        return $patch;
    }

    public function detectErrors()
    {
        return $this->file->detectErrors();
    }
}
