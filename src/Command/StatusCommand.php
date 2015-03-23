<?php
namespace Jibriss\Dbvc\Command;

use Jibriss\Dbvc\Dbvc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('status')
            ->setDescription('Display details about all the patches and versions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var TableHelper $table */
        $table = $this->getHelper('table');
        $table->setLayout(TableHelper::LAYOUT_COMPACT);
        $table->setVerticalBorderChar('   ');

        if (count($patches = $this->dbvc->getStatus('patch')) > 0) {
            $table->setHeaders(array('Patch name', 'On disk', 'In DB'));

            foreach ($patches as $patch) {
                $table->addRow(array(
                    $patch['name'],
                    $patch['on_disk']
                        ? ($patch['changed'] ? '<fg=red>Changed</fg=red>' : 'Yes')
                        : 'No',
                    $patch['in_db'] ? 'Yes' : '<fg=red>No</fg=red>',
                ));
            }

            $table->render($output);
            $table->setRows(array());
            $output->writeln('');
        }

        if (count($tags = $this->dbvc->getStatus('tag')) > 0) {
            $table->setHeaders(array('Tag name', 'On disk', 'In DB'));

            foreach ($tags as $tag) {
                $table->addRow(array(
                    $tag['name'],
                    $tag['on_disk'] ? 'Yes' : 'No',
                    $tag['in_db'] ? 'Yes' : '<fg=red>No</fg=red>',
                ));
            }

            $table->render($output);
            $table->setRows(array());
            $output->writeln('');
        }
    }
}
