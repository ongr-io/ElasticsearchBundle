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
            ->addOption('time', 't', InputOption::VALUE_NONE, 'Adds date suffix to the new index name')
            ->addOption(
                'alias',
                'a',
                InputOption::VALUE_NONE,
                'If the time suffix is used, its nice to create an alias to the configured index name.'
            )
            ->addOption('no-mapping', null, InputOption::VALUE_NONE, 'Do not include mapping')
            ->addOption(
                'if-not-exists',
                null,
                InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the index already exists'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getManager($input->getOption('manager'));
        $originalIndexName = $manager->getIndexName();

        if ($input->getOption('time')) {
            /** @var IndexSuffixFinder $finder */
            $finder = $this->getContainer()->get('es.client.index_suffix_finder');
            $finder->setNextFreeIndex($manager);
        }

        if ($input->getOption('if-not-exists') && $manager->indexExists()) {
            $output->writeln(
                sprintf(
                    '<info>Index `<comment>%s</comment>` already exists in `<comment>%s</comment>` manager.</info>',
                    $manager->getIndexName(),
                    $input->getOption('manager')
                )
            );

            return 0;
        }

        $manager->createIndex($input->getOption('no-mapping'));

        $output->writeln(
            sprintf(
                '<info>Created `<comment>%s</comment>` index for the `<comment>%s</comment>` manager.</info>',
                $manager->getIndexName(),
                $input->getOption('manager')
            )
        );

        if ($input->getOption('alias') && $originalIndexName != $manager->getIndexName()) {
            $manager->getClient()->indices()->putAlias(
                [
                    'index' => $manager->getIndexName(),
                    'name' => $originalIndexName,
                ]
            );

            $output->writeln(
                sprintf(
                    '<info>Created an alias `<comment>%s</comment>` for the `<comment>%s</comment>` index.</info>',
                    $originalIndexName,
                    $manager->getIndexName()
                )
            );

            if ($manager->getClient()->indices()->existsAlias(['name' => $originalIndexName])) {
                $currentAlias = $manager->getClient()->indices()->getAlias(
                    [
                        'name' => $originalIndexName,
                    ]
                );

                if (isset($currentAlias[$manager->getIndexName()])) {
                    unset($currentAlias[$manager->getIndexName()]);
                }

                $indexesToRemoveAliases = implode(',', array_keys($currentAlias));
                if (!empty($indexesToRemoveAliases)) {
                    $manager->getClient()->indices()->deleteAlias(
                        [
                            'index' => $indexesToRemoveAliases,
                            'name' => $originalIndexName,
                        ]
                    );

                    $output->writeln(
                        sprintf(
                            '<info>Removed `<comment>%s</comment>` alias from `<comment>%s</comment>`' .
                            'index(es).</info>',
                            $originalIndexName,
                            $indexesToRemoveAliases
                        )
                    );
                }
            }
        }
    }
}
