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

class CacheClearCommand extends AbstractIndexServiceAwareCommand
{
    const NAME = 'ongr:es:cache:clear';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription('Clears ElasticSearch client\'s cache.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $index = $this->getIndex($input->getOption('index'));
        $index->clearCache();

        $io->success(
            sprintf(
                'Elasticsearch `%s` index cache has been cleared.',
                $index->getIndexName()
            )
        );

        return 0;
    }
}
