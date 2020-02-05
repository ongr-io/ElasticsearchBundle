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

use ONGR\ElasticsearchBundle\Service\ExportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexExportCommand extends AbstractIndexServiceAwareCommand
{
    const NAME = 'ongr:es:index:export';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription('Exports a data from the ElasticSearch index.')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Define a filename to store the output'
            )->addOption(
                'chunk',
                null,
                InputOption::VALUE_REQUIRED,
                'Chunk size to use in the scan api',
                500
            )->addOption(
                'split',
                null,
                InputOption::VALUE_REQUIRED,
                'Split a file content in a separate parts if a line number exceeds provided value',
                300000
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $index = $this->getIndex($input->getOption(parent::INDEX_OPTION));

        /** @var ExportService $exportService */
        $exportService = $this->getContainer()->get(ExportService::class);
        $exportService->exportIndex(
            $index,
            $input->getArgument('filename'),
            $input->getOption('chunk'),
            $output,
            $input->getOption('split')
        );

        $io->success('Data export completed!');

        return 0;
    }
}
