<?php
namespace Jibriss\Dbvc\Command;

use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PatchCreateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('patch:create')
            ->addArgument('patch_name', InputArgument::REQUIRED, 'Name of the patch to create')
            ->addOption('with-date', 'd', InputOption::VALUE_NONE, 'Prefix the patch name with the current date')
            ->setDescription('Create new empty patch files : migration and rollback')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('patch_name');

        if ($input->hasOption('with-date')) {
            $name = date('YmdHis').'-'.$name;
        }

        try {
            $patch = $this->dbvc->createNewPatch($name);
            $output->writeln("Patch created");
        } catch (Exception $e) {
            $output->writeln("This patch already exists");
        }
    }
}
