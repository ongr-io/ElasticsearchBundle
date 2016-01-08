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
class IndexImportCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:index:import')
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
        $manager = $this->getManager($input->getOption('manager'));

        $options = [];
        if ($input->getOption('gzip')) {
            $options['gzip'] = true;
        }

        /** @var ImportService $importService */
        $importService = $this->getContainer()->get('es.import');
        $importService->importIndex(
            $manager,
            $input->getArgument('filename'),
            $output,
            $input->getOption('bulk-size'),
            $options
        );

        $output->writeln('<info>Data import completed!</info>');
    }
}
