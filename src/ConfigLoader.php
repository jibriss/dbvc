<?php
namespace Jibriss\Dbvc;

class ConfigLoader
{
    public function loadConfig($config)
    {
        $dir = getcwd();
        while (!file_exists($configPath = rtrim($dir, '/') . '/' . $config)) {
            $dir = dirname($dir);

            if ($dir == '/') {
                throw new \RuntimeException("Unable to find configuration file '$config'");
            }
        }

        // En cas d'erreur, une exception est levée
        @$xml = new \SimpleXMLElement(file_get_contents($configPath));

        return $this->simpleXmlToArray($xml);
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
