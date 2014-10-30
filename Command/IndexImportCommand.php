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

/**
 * IndexImportCommand class.
 */
class IndexImportCommand extends AbstractElasticsearchCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('es:index:import')
            ->setDescription('Imports data to Elasticsearch index')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Select file to store output'
            )
            ->addOption('raw', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getManager($input->getOption('manager'));

        /* @var ImportService $importService */
        $importService = $this->getContainer()->get('es.import');
        $importService->importIndex($manager, $input->getArgument('filename'), $input->getOption('raw'), $output);
    }
}
