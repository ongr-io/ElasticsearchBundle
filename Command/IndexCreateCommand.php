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

use ONGR\ElasticsearchBundle\Service\IndexSuffixFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexCreateCommand extends AbstractIndexServiceAwareCommand
{
    const NAME = 'ongr:es:index:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription('Creates the ElasticSearch index.')
            ->addOption(
                'time',
                't',
                InputOption::VALUE_NONE,
                'Adds date suffix to the new index name.'
            )
            ->addOption(
                'alias',
                'a',
                InputOption::VALUE_NONE,
                'Adds alias as it is defined in the Index document annotation.'
            )
            ->addOption(
                'no-mapping',
                null,
                InputOption::VALUE_NONE,
                'Do not include mapping when the index is created.'
            )
            ->addOption(
                'if-not-exists',
                null,
                InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the index already exists.'
            )
            ->addOption(
                'dump',
                null,
                InputOption::VALUE_NONE,
                'Prints a json output of the index mapping.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $index = $this->getIndex($input->getOption(parent::INDEX_OPTION));
        $indexName = $aliasName = $index->getIndexName();

        if ($input->getOption('dump')) {
            $io->note("Index mappings:");
            $io->text(
                json_encode(
                    $index->getIndexSettings()->getIndexMetadata(),
                    JSON_PRETTY_PRINT
                )
            );

            return 0;
        }

        if ($input->getOption('time')) {
            /** @var IndexSuffixFinder $finder */
            $finder = $this->getContainer()->get(IndexSuffixFinder::class);
            $indexName = $finder->getNextFreeIndex($index);
        }

        if ($input->getOption('if-not-exists') && $index->indexExists()) {
            $io->note(
                sprintf(
                    'Index `%s` already exists.',
                    $index->getIndexName()
                )
            );

            return 0;
        }

        $indexesToRemoveAliases = null;
        if ($input->getOption('alias') && $index->getClient()->indices()->existsAlias(['name' => $aliasName])) {
            $indexesToRemoveAliases = $index->getClient()->indices()->getAlias(
                [
                    'name' => $aliasName,
                ]
            );
        }

        $index->createIndex($input->getOption('no-mapping'), array_filter([
            'index' => $indexName,
        ]));

        $io->text(
            sprintf(
                'Created `<comment>%s</comment>` index.',
                $index->getIndexName()
            )
        );

        if ($input->getOption('alias')) {
            $index->getClient()->indices()->putAlias([
                'index' => $indexName,
                'name' => $aliasName,
            ]);
            $io->text(
                sprintf(
                    'Created an alias `<comment>%s</comment>` for the `<comment>%s</comment>` index.',
                    $aliasName,
                    $indexName
                )
            );
        }

        if ($indexesToRemoveAliases) {
            $indexesToRemoveAliases = implode(',', array_keys($indexesToRemoveAliases));
            $index->getClient()->indices()->deleteAlias([
                'index' => $indexesToRemoveAliases,
                'name' => $aliasName,
            ]);
            $io->text(
                sprintf(
                    'Removed `<comment>%s</comment>` alias from `<comment>%s</comment>`.',
                    $aliasName,
                    $indexesToRemoveAliases
                )
            );
        }

        return 0;
    }
}
