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

/**
 * Command for dropping Elasticsearch index.
 */
class IndexDropCommand extends AbstractManagerAwareCommand
{
    public static $defaultName = 'ongr:es:index:drop';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(static::$defaultName)
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
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('force')) {
            $this->getManager($input->getOption('manager'))->dropIndex();

            $io->text(
                sprintf(
                    'Dropped index for the <comment>`%s`</comment> manager',
                    $input->getOption('manager')
                )
            );
        } else {
            $io->error('ATTENTION:');
            $io->text('This action should not be used in the production environment.');
            $io->error('Option --force is mandatory to drop type(s).');
        }
    }
}
