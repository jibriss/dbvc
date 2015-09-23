<?php
namespace Jibriss\Dbvc\Command;

use Jibriss\Dbvc\Dbvc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends DbvcCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('status')
            ->setDescription('Display details about all the patches and tags')
            ->addOption('limit-tag', 'l', InputOption::VALUE_REQUIRED, 'Number of tags to display or "all"', '10')
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
                        : '<fg=red>No</fg=red>',
                    $patch['in_db'] ? 'Yes' : '<fg=red>No</fg=red>',
                ));
            }

            $table->render($output);
            $table->setRows(array());
            $output->writeln('');
        }

        $tagCount = count($tags = $this->dbvc->getStatus('tag'));

        if ($tagCount > 0) {

            $limitTag = $input->getOption('limit-tag');

            if ($limitTag != 'all') {
                if ((int)$limitTag < 1) {
                    throw new \InvalidArgumentException('--limit-tag option must be a positive integer or "all"');
                }

                if ((int)$limitTag < $tagCount) {
                    $table->addRow(array('[...]'));
                }
                $tags = array_slice($tags, -(int)$limitTag);
            }

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

        if (count($tags) == 0 && count($patches) == 0) {
            $output->writeln('There is no patch/tag available');
        }
    }
}
