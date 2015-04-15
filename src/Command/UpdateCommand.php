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

class UpdateCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('update')
            ->setDescription('Update you database with all the tag/patch possible. This may require some rollback')
            ->addOption('without-script', 'w', InputOption::VALUE_NONE, 'Only update the version table, do not execute the migration script')
            ->addOption('full', 'u', InputOption::VALUE_NONE, 'Rollback patches in DB but not on disk')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nothingToDo = true;
        $withoutScript = $input->getOption('without-script');

        if ($input->getOption('full') === true) {
            foreach ($this->dbvc->getAllPatchesToRollback() as $patch) {
                $nothingToDo = false;
                $output->writeln(">>> <info>Rollbacking patch '{$patch['name']}'</info>");
                $output->writeln('You are about to execute this SQL script on your database :');
                $output->writeln("<comment>{$patch['rollback']}</comment>");

                if ($this->askConfirmation($output)) {
                    $this->dbvc->rollback($patch, $withoutScript);
                    $output->writeln("Patch rollbacked");
                } else {
                    $output->writeln("Rollback aborted by user");
                    break;
                }
            }
        }

        if ($nbtag = count($tags = $this->dbvc->getAllTagToMigrate()) > 0) {
            $nothingToDo = false;
            $patches = $this->dbvc->getAllPatchesInDb();

            if ($nbPatch = count($patches) > 0) {
                $output->writeln("Your DB is missing $nbtag tag(s), but have $nbPatch patch(s)");
                $output->writeln("You can't migrate tag on a DB containing patches");
                $question = 'Do you want to rollback these patches, migrate tags then re-apply the patches ?';

                if ($this->getHelper('dialog')->askConfirmation($output, "<question>$question</question> ", false)) {
                    foreach ($patches as $patch) {
                        $output->writeln(">>> <info>Rollbacking patch '{$patch['name']}'</info>");
                        $output->writeln('You are about to execute this SQL script on your database :');
                        $output->writeln("<comment>{$patch['rollback']}</comment>");

                        if ($this->askConfirmation($output)) {
                            $this->dbvc->rollback($patch, $withoutScript);
                            $output->writeln("Patch rollbacked");
                        } else {
                            $output->writeln("The tags won't be migrated");
                            $tags = array();
                        }
                    }
                } else {
                    $output->writeln("The tags won't be migrated");
                    $tags = array();
                }
            }

            foreach ($tags as $tag) {
                $output->writeln(">>> <info>Migrating tag '{$tag['name']}'</info>");
                $output->writeln('You are about to execute this SQL script on your database :');
                $output->writeln("<comment>{$tag['migration']}</comment>");

                if ($this->askConfirmation($output)) {
                    $this->dbvc->migrate($tag, $withoutScript);
                    $output->writeln("Tag migrated");
                } else {
                    $output->writeln("Command aborted by user");
                    break;
                }
            }
        }

        $patchesToRollback = $this->dbvc->getAllPatchesThatChanged();

        foreach ($patchesToRollback as $patch) {
            $nothingToDo = false;
            $output->writeln("The patch file '{$patch['name']}' has changed, and need to be rollbacked");
            $output->writeln(">>> <info>Rollbacking patch {$patch['name']}</info>");
            $output->writeln("The last version of the patch will be migrated right after");
            $output->writeln('You are about to execute this SQL script on your database :');
            $output->writeln("<comment>{$patch['rollback']}</comment>");

            if ($this->askConfirmation($output)) {
                $this->dbvc->rollback($patch, $withoutScript);
                $output->writeln("Patch migrated");
            } else {
                $output->writeln("Command aborted by user");
            }
        }

        $patchesToMigrate = $this->dbvc->getAllPatchesNotInDb();

        foreach ($patchesToMigrate as $patch) {
            $nothingToDo = false;
            $output->writeln(">>> <info>Migrating patch {$patch['name']}</info>");
            $output->writeln('You are about to execute this SQL script on your database :');
            $output->writeln("<comment>{$patch['migration']}</comment>");

            if ($this->askConfirmation($output)) {
                $this->dbvc->migrate($patch, $withoutScript);
                $output->writeln("Patch migrated");
            } else {
                $output->writeln("Command aborted by user");
            }
        }

        if ($nothingToDo) {
            $output->writeln("Your database is already up-to-date.");
        }
    }
}
