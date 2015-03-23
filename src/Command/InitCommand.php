<?php
namespace Jibriss\Dbvc\Command;

use Jibriss\Dbvc\Dbvc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('init')
            ->setDescription('Create a dbvc configuration file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = rtrim(getcwd(), '/') . '/dbvc.xml';

        if (file_exists($filePath)) {
            $output->writeln("The config file <info>$filePath</info> already exists");
        } else {
            if (@copy(__DIR__ . '/../../config.example.xml', $filePath)) {
                $output->writeln("Config file <info>$filePath</info> created");
                $output->writeln("Please edit it to configure dbvc");
            } else {
                $output->writeln("<error>Unable to create file $filePath. Is the directory writeable ?</error>");
            }
        }
    }
}
