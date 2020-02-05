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
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexDropCommand extends AbstractIndexServiceAwareCommand
{
    const NAME = 'ongr:es:index:drop';

    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription('Drops ElasticSearch index.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force option is mandatory to drop the index.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('force')) {
            $index = $this->getIndex($input->getOption(parent::INDEX_OPTION));

            $client =

            $result = $index->dropIndex();

            $io->text(
                sprintf(
                    'The index <comment>`%s`</comment> was successfully dropped.',
                    $index->getIndexName()
                )
            );
        } else {
            $io->error('WARNING:');
            $io->text('This action should not be used in the production environment.');
            $io->error('Option --force is mandatory to drop the index.');
        }

        return 0;
    }
}
