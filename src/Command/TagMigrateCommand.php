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

class TagMigrateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('tag:migrate')
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

        foreach ($tags as $tag) {
            $output->writeln(">>> <info>Migrating to tag '{$tag['name']}'</info>");
            $output->writeln('You are about to execute this SQL script on your database :');
            $output->writeln("<comment>{$tag['migration']}</comment>");

            if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                $output->writeln("Migration aborted by user");
                return;
            } else {
                $this->dbvc->migrate($tag);
                $output->writeln("Tag migration done");
            }
        }
    }
}
