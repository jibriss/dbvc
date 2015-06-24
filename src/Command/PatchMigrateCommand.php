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
            ->addOption('without-script', 'w', InputOption::VALUE_NONE, 'Only update the version table, do not execute the migration script')
            ->setDescription('Apply a patch to the database. If the patch is already in DB but the file changed on disk, this will rollback and re-apply the patch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name          = $input->getArgument('patch_name');
        $withoutScript = $input->getOption('without-script');
        $patch         = $this->dbvc->getVersion('patch', $name);

        if (!$patch['on_disk']) {
            $output->writeln('This patch is not on disk');
            return;
        }

        if ($patch['changed']) {
            $output->writeln("The patch '$name' file on disk has changed. You need to rollback then re-apply");

            $output->writeln(">>> <info>Rollbacking patch '{$patch['name']}'</info>");
            $this->displaySql($output, $patch['rollback'], $withoutScript);

            if ($this->askConfirmation($output)) {
                $this->dbvc->rollback($patch, $withoutScript);
                $output->writeln("Patch rollbacked");
            } else {
                $output->writeln("Command aborted by user");
                return;
            }
        }

        $patch = $this->dbvc->getVersion('patch', $name);

        if ($patch['in_db']) {
            $output->writeln('This patch is already up-to-date in DB');
        } else {
            $output->writeln(">>> <info>Migrating patch '{$patch['name']}'</info>");

            $this->displaySql($output, $patch['migration'], $withoutScript);

            if ($this->askConfirmation($output)) {
                $this->dbvc->migrate($patch, $withoutScript);
                $output->writeln("Patch migrated");
            } else {
                $output->writeln("Command aborted by user");
            }
        }
    }
}
