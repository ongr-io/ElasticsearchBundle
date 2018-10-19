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

/**
 * IndexExportCommand class.
 */
class IndexExportCommand extends AbstractManagerAwareCommand
{
    public static $defaultName = 'ongr:es:index:export';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(static::$defaultName)
            ->setDescription('Exports data from elasticsearch index.')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Select file to store output'
            )->addOption(
                'types',
                null,
                InputOption::VALUE_REQUIRED + InputOption::VALUE_IS_ARRAY,
                'Export specific types only'
            )->addOption(
                'chunk',
                null,
                InputOption::VALUE_REQUIRED,
                'Chunk size to use in scan api',
                500
            )->addOption(
                'split',
                null,
                InputOption::VALUE_REQUIRED,
                'Split file in a separate parts if line number exceeds provided value',
                300000
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $manager = $this->getManager($input->getOption('manager'));

        /** @var ExportService $exportService */
        $exportService = $this->getContainer()->get('es.export');
        $exportService->exportIndex(
            $manager,
            $input->getArgument('filename'),
            $input->getOption('types'),
            $input->getOption('chunk'),
            $output,
            $input->getOption('split')
        );

        $io->success('Data export completed!');
    }
}
