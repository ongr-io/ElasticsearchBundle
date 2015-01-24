<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Command;

use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for dropping Elasticsearch index.
 */
class IndexDropCommand extends AbstractConnectionAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('es:index:drop')
            ->setDescription('Drops elasticsearch index.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $this->getConnection($input->getOption('connection'))->dropIndex();

            $output->writeln(
                sprintf(
                    '<info>Dropped index for connection named</info> <comment>`%s`</comment>',
                    $input->getOption('connection')
                )
            );
        } else {
            $output->writeln('<info>Parameter --force has to be used to drop the index.</info>');
        }
    }
}
