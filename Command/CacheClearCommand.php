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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony command for clearing elasticsearch cache.
 */
class CacheClearCommand extends AbstractElasticsearchCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('es:cache:clear')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED,
                'Connection name to which clear cache.',
                'default'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getConnection($input->getOption('connection'))->clearCache();
        $output->writeln(
            sprintf(
                'Elasticsearch cache has been cleared for <info>%s</info> index.',
                $input->getOption('connection')
            )
        );

        return 0;
    }
}
