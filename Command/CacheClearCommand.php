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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony command for clearing elasticsearch cache.
 */
class CacheClearCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:cache:clear')
            ->setDescription('Clears elasticsearch client cache.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->getManager($input->getOption('manager'))
            ->getConnection()
            ->clearCache();
        $output->writeln(
            sprintf(
                '<info>Elasticsearch index cache has been cleared for manager named</info> </comment>`%s`</comment>',
                $input->getOption('manager')
            )
        );

        return 0;
    }
}
