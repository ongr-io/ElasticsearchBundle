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

use ONGR\ElasticsearchBundle\Client\IndexSuffixFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating elasticsearch index.
 */
class IndexCreateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:index:create')
            ->setDescription('Creates elasticsearch index.')
            ->addOption('time', 't', InputOption::VALUE_NONE, 'Adds date suffix to new index name')
            ->addOption('with-warmers', 'w', InputOption::VALUE_NONE, 'Puts warmers into index')
            ->addOption('no-mapping', 'm', InputOption::VALUE_NONE, 'Do not include mapping');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getManager($input->getOption('manager'))->getConnection();

        if ($input->getOption('time')) {
            /** @var IndexSuffixFinder $finder */
            $finder = $this->getContainer()->get('es.client.index_suffix_finder');
            $finder->setNextFreeIndex($connection);
        }
        $connection->createIndex($input->getOption('with-warmers'), $input->getOption('no-mapping') ? true : false);
        $output->writeln(
            sprintf(
                '<info>Created index for manager named `</info><comment>%s</comment><info>`</info>',
                $input->getOption('manager')
            )
        );
    }
}
