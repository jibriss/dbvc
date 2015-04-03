<?php
namespace Jibriss\Dbvc\Command;

use Doctrine\DBAL\DBALException;
use Jibriss\Dbvc\ConfigLoader;
use Jibriss\Dbvc\Db;
use Jibriss\Dbvc\Dbvc;
use Jibriss\Dbvc\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DbvcCommand extends Command
{
    /** @var Dbvc */
    protected $dbvc;

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a dbvc configuration file')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'The configuration file to use', 'dbvc.xml')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getApplication()->getLongVersion() . PHP_EOL);
        $this->setCode(array($this, 'safeExecute'));
        $configLoader = new ConfigLoader();

        try {
            $config = $configLoader->loadConfig($input->getOption('config'));
        } catch (\Exception $e) {
            $output->writeln("<error>DBVC configuration error</error>");
            $output->writeln("<error>{$e->getMessage()}</error>");

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                throw $e;
            }

            return;
        }

        try {
            $dbal = \Doctrine\DBAL\DriverManager::getConnection($config['db']);
        } catch (DBALException $e) {
            $output->writeln("<error>The configuration 'db' is not valid</error>");
            $output->writeln("<error>{$e->getMessage()}</error>");

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                throw $e;
            }

            return;
        }

        $db = new Db($dbal, $config['dbvc_table']);
        $db->createMigrationsTableIfNotExists();

        $this->dbvc = new Dbvc(new File($config['patches_directory'], $config['tags_directory']), $db);

        foreach ($this->dbvc->detectErrors() as $error) {
            $output->writeln("<error>$error</error>\n");
        }
    }

    /**
     * Vérifie juste que dbvc a bien été initialisé, pour éviter une erreur fatal ailleurs
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function safeExecute(InputInterface $input, OutputInterface $output)
    {
        if (!isset($this->dbvc)) {
            return 1;
        }

        return $this->execute($input, $output);
    }

    protected function askConfirmation(OutputInterface $output, $question = 'Are you sure ?', $default = true)
    {
        return $this->getHelper('dialog')->askConfirmation($output, "<question>$question</question> ", $default);
    }
}
