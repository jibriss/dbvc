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

class PatchMigrateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('patch:migrate')
            ->addArgument('patch_name', InputArgument::REQUIRED, 'Name of the patch to apply')
            ->addOption('without-script', 'w', InputOption::VALUE_NONE, 'Just update the version table, do not execute the migration script')
            ->setDescription('Apply a patch to the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('patch_name');
        $patch = $this->dbvc->getVersion('patch', $name);

        if (!$patch['on_disk']) {
            $output->writeln('This patch is not on disk');
        } elseif ($patch['in_db']) {
            $output->writeln('This patch is already in DB');
        } else {
            $withoutScript = $input->getOption('without-script');
            $output->writeln(">>> <info>Migrating patch '{$patch['name']}'</info>");

            if ($withoutScript) {
                $output->writeln("The script won't be executed");
            } else {
                $output->writeln('You are about to execute this SQL script on your database :');
                $output->writeln("<comment>{$patch['migration']}</comment>");
            }

            if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                $output->writeln("Command aborted by user");
            } else {
                $this->dbvc->migrate($patch, $withoutScript);

                $output->writeln("Patch migrated");
            }
        }
    }
}
