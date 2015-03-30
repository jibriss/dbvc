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

class TagCreateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('tag:create')
            ->setDescription('Create a new tag from all the patches applied to your DB')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (count($this->dbvc->getAllTagToMigrate()) > 0) {
            $output->writeln('You have to migrate your DB to the last tag before creating a new one');
            $output->writeln('Trying running <info>tag:migrate</info>');
            return;
        }

        $patches = $this->dbvc->getAllPatchesInDb();

        if (count($patches) == 0) {
            $output->writeln('You have to migrate at least 1 patch to create a new tag');
            return;
        }

        $nextTag = $this->dbvc->getNextTagName();
        $output->writeln(">>> <info>Creating tag {$nextTag}</info>");
        $output->writeln("It will be created by merging the migration/rollback files of these patches :");

        foreach ($patches as $patch) {
            $output->writeln(" - {$patch['name']}");
        }

        if ($this->askConfirmation($output, 'Create the new tag ?')) {
            $tag = $this->dbvc->createNewTag();
            $output->writeln("New tag created '{$tag['name']}'");
        } else {
            $output->writeln("Tag creation aborted by user");
        }
    }
}
