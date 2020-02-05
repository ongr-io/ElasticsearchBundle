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

use ONGR\ElasticsearchBundle\Service\ImportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexImportCommand extends AbstractIndexServiceAwareCommand
{
    const NAME = 'ongr:es:index:import';
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription('Imports data to elasticsearch index.')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Select file to store output'
            )
            ->addOption(
                'bulk-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Set bulk size for import',
                1000
            )
            ->addOption(
                'gzip',
                'z',
                InputOption::VALUE_NONE,
                'Import a gzip file'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $index = $this->getIndex($input->getOption(parent::INDEX_OPTION));

        // Initialize options array
        $options = [];
        if ($input->getOption('gzip')) {
            $options['gzip'] = null;
        }
        $options['bulk-size'] = $input->getOption('bulk-size');

        /** @var ImportService $importService */
        $importService = $this->getContainer()->get(ImportService::class);
        $importService->importIndex(
            $index,
            $input->getArgument('filename'),
            $output,
            $options
        );

        $io->success('Data import completed!');

        return 0;
    }
}
