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

class TagRollbackCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('tag:rollback')
            ->addArgument('to', InputArgument::REQUIRED, 'The tag to rollback to')
            ->addOption('without-script', 'w', InputOption::VALUE_NONE, 'Only update the version table, do not execute the migration script')
            ->setDescription('Rollback your database to a previous tag')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->dbvc->isThereAnyPatchInDb()) {
            $output->writeln('You have to rollback all the patches applied to you DB before rollbacking tags');
            return;
        }

        $targetTag = $this->dbvc->getVersion('tag', $input->getArgument('to'));

        if (!$targetTag['in_db']) {
            $output->writeln("Impossible to rollback to a tag not in db");
            return;
        }

        if (count($tags = $this->dbvc->getAllTagToRollback($targetTag['name'])) === 0) {
            $output->writeln("You are already at this tag");
            return;
        }

        foreach ($tags as $tag) {
            $withoutScript = $input->getOption('without-script');
            $output->writeln(">>> <info>Rollbacking tag '{$tag['name']}'</info>");
            $this->displaySql($output, $tag['rollback'], $withoutScript);

            if ($this->askConfirmation($output)) {
                $this->dbvc->rollback($tag, $withoutScript);
                $output->writeln("Rollback done");
            } else {
                $output->writeln("Rollback aborted by user");
                return;
            }
        }
    }
}
