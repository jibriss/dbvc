<?php
namespace Jibriss\Dbvc\Command;

use Jibriss\Dbvc\Dbvc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PatchRollbackCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('patch:rollback')
            ->addArgument('patch_name', InputArgument::REQUIRED, 'Name of the patch to rollback')
            ->addOption('without-script', 'w', InputOption::VALUE_NONE, 'Only update the version table, do not execute the migration script')
            ->setDescription('Rollback a patch from the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('patch_name');
        $patch = $this->dbvc->getVersion('patch', $name);

        if (!$patch['in_db']) {
            $output->writeln('This patch is not in db');
        } else {
            $withoutScript = $input->getOption('without-script');
            $output->writeln(">>> <info>Rollbacking patch '{$patch['name']}'</info>");

            if ($withoutScript) {
                $output->writeln("The script won't be executed");
            } else {
                $output->writeln('You are about to execute this SQL script on your database :');
                $output->writeln("<comment>{$patch['rollback']}</comment>");
            }

            if ($this->askConfirmation($output)) {
                $this->dbvc->rollback($patch, $withoutScript);
                $output->writeln("Rollback done");
            } else {
                $output->writeln("Command aborted by user");
            }
        }
    }
}
