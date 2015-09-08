<?php
namespace Jibriss\Dbvc\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PatchCreateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('patch:create')
            ->addArgument('patch_name', InputArgument::REQUIRED, 'Name of the patch to apply')
            ->setDescription('Create new empty patch files : migration and rollback')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('patch_name');

        try {
            $patch = $this->dbvc->createNewPatch($name);
            $output->writeln("Patch created");

        } catch (Exception $e) {
            $output->writeln("This patch already exists");
        }



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
