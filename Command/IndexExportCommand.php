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

/**
 * IndexExportCommand class.
 */
class IndexExportCommand extends AbstractElasticsearchCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('es:index:export')
            ->setDescription('Exports elasticsearch index')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Select file to store output'
            )->addOption(
                'chunk',
                null,
                InputOption::VALUE_REQUIRED,
                'Chunk size to use in scan api',
                500
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getManager($input->getOption('manager'));

        /* @var ExportService $exportService */
        $exportService = $this->getContainer()->get('es.export');
        $exportService->exportIndex($manager, $input->getArgument('filename'), $input->getOption('chunk'), $output);
    }
}
