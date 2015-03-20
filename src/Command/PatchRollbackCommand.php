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

class PatchRollbackCommand extends Command
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
            ->setName('patch:rollback')
            ->addArgument('patch_name', InputArgument::REQUIRED, 'Name of the patch to rollback')
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
            $output->writeln(">>> <info>Rollbacking patch '{$patch['name']}'</info>");
            $output->writeln('You are about to execute this SQL script on your database :');
            $output->writeln("<comment>{$patch['rollback']}</comment>");

            if (!$this->getHelper('dialog')->askConfirmation($output, '<question>Are you sure ?</question> ', false)) {
                $output->writeln("Command aborted by user");
            } else {
                $this->dbvc->rollback($patch);
            }
        }
    }
}
