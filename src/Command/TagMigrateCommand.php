<?php
namespace Jibriss\Dbvc\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TagMigrateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('tag:migrate')
            ->addOption('without-script', 'w', InputOption::VALUE_NONE, 'Only update the version table, do not execute the migration script')
            ->setDescription('Update your database to the last tag')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->dbvc->isThereAnyPatchInDb()) {
            $output->writeln('You have to rollback all the patches applied to you db before migrating tags');
            return;
        }

        if (count($tags = $this->dbvc->getAllTagToMigrate()) == 0) {
            $output->writeln('Your database is already at the lastest tag available');
            return;
        }

        $withoutScript = $input->getOption('without-script');

        foreach ($tags as $tag) {
            $output->writeln(">>> <info>Migrating to tag '{$tag['name']}'</info>");

            if ($withoutScript) {
                $output->writeln("The script won't be executed");
            } else {
                $output->writeln('You are about to execute this SQL script on your database :');
                $output->writeln("<comment>{$tag['migration']}</comment>");
            }

            if ($this->askConfirmation($output)) {
                $this->dbvc->migrate($tag, $withoutScript);
                $output->writeln("Tag migration done");
            } else {
                $output->writeln("Migration aborted by user");
                return;
            }
        }
    }
}
