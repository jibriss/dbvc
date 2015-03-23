<?php
namespace Jibriss\Dbvc;

class ConfigLoader
{
    public function loadConfig($config)
    {
        $dir = rtrim(getcwd(), '/') . '/';

        while (!file_exists($configPath = $dir . $config)) {
            $dir = rtrim(dirname($dir), '/') . '/';

            if ($dir == '/') {
                throw new \RuntimeException("Unable to find configuration file '$config'");
            }
        }

        // En cas d'erreur, une exception est levée
        @$xml = new \SimpleXMLElement(file_get_contents($configPath));

        $config = $this->simpleXmlToArray($xml);

        if (isset($config['patches_directory'])) {
            if (!file_exists($config['patches_directory'])) {
                $config['patches_directory'] = $dir . $config['patches_directory'];

                if (!file_exists($config['patches_directory'])) {
                    throw new \RuntimeException("Directory '{$config['patches_directory']}' not found");
                }
            }
        } else {
            throw new \RuntimeException("Config 'patches_directory' is missing");
        }

        if (isset($config['tags_directory'])) {
            if (!file_exists($config['tags_directory'])) {
                $config['tags_directory'] = $dir . $config['tags_directory'];

                if (!file_exists($config['tags_directory'])) {
                    throw new \RuntimeException("Directory '{$config['tags_directory']}' not found");
                }
            }
        } else {
            throw new \RuntimeException("Config 'tags_directory' is missing");
        }

        return $config;
    }

    private function simpleXmlToArray(\SimpleXMLElement $xml)
    {
        if (count($xml->children()) > 0) {
            $config = array();

            foreach ($xml->children() as $name => $value) {
                $config[$name] = $this->simpleXmlToArray($value);
            }

            return $config;
        } else {
            return (string) $xml;
        }
    }
}
