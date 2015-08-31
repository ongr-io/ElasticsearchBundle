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

/**
 * Command for dropping Elasticsearch index.
 */
class IndexDropCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:index:drop')
            ->setDescription('Drops elasticsearch index.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $this->getManager($input->getOption('manager'))->getConnection()->dropIndex();

            $output->writeln(
                sprintf(
                    '<info>Dropped index for the </info> <comment>`%s`</comment> manager',
                    $input->getOption('manager')
                )
            );
        } else {
            $output->writeln('<error>ATTENTION:</error> This action should not be used in the production environment.');
            $output->writeln('<error>"Option --force is mandatory to drop type(s)."</error> ');
        }
    }
}
