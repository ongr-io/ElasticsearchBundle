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
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony command for clearing elasticsearch cache.
 */
class CacheClearCommand extends AbstractManagerAwareCommand
{
    public static $defaultName = 'ongr:es:cache:clear';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(static::$defaultName)
            ->setDescription('Clears elasticsearch client cache.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this
            ->getManager($input->getOption('manager'))
            ->clearCache();
        $io->success(
            sprintf(
                'Elasticsearch index cache has been cleared for manager named `%s`',
                $input->getOption('manager')
            )
        );
    }
}
