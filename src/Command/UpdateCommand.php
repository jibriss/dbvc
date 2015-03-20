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

class UpdateCommand extends Command
{
    /**
     * @var \Jibriss\Dbvc\Dbvc
     */
    private $dbvc;

    public function __construct(Dbvc $dbvc)
    {
        parent::__construct();
        $this->dbvc = $dbvc;
    }

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update you database with all the tag/patch possible. This may require some rollback')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($nbtag = count($tags = $this->dbvc->getAllTagToMigrate()) > 0) {
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

                        if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                            $output->writeln("The tags won't be migrated");
                            $tags = array();
                        } else {
                            $this->dbvc->rollback($patch);
                            $output->writeln("Patch migrated");
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

                if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                    $output->writeln("Command aborted by user");
                    break;
                } else {
                    $this->dbvc->migrate($tag);
                    $output->writeln("Tag migrated");
                }
            }
        }

        $patchesToRollback = $this->dbvc->getAllPatchesThatChanged();

        foreach ($patchesToRollback as $patch) {
            $output->writeln("The patch file '{$patch['name']}' has changed, and need to be rollbacked");
            $output->writeln(">>> <info>Rollbacking patch {$patch['name']}</info>");
            $output->writeln("The last version of the patch will be migrated right after");
            $output->writeln('You are about to execute this SQL script on your database :');
            $output->writeln("<comment>{$patch['rollback']}</comment>");

            if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                $output->writeln("Command aborted by user");
            } else {
                $this->dbvc->rollback($patch);
                $output->writeln("Patch migrated");
            }
        }

        $patchesToMigrate = $this->dbvc->getAllPatchesNotInDb();

        foreach ($patchesToMigrate as $patch) {
            $output->writeln(">>> <info>Migrating patch {$patch['name']}</info>");
            $output->writeln('You are about to execute this SQL script on your database :');
            $output->writeln("<comment>{$patch['migration']}</comment>");

            if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                $output->writeln("Command aborted by user");
            } else {
                $this->dbvc->migrate($patch);
                $output->writeln("Patch migrated");
            }
        }
    }
}
