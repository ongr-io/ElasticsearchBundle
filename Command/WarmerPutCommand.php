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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command puts warmers into elasticsearch index.
 */
class WarmerPutCommand extends AbstractWarmerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('es:warmer:put');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $names = $input->getArgument('names');
        $this->getConnection($input->getOption('connection'))->putWarmers($names);

        if (empty($names)) {
            $message = 'All warmers have been put into <info>%s</info> index.';
        } else {
            $callback = function ($val) {
                return '<info>' . $val . '</info>';
            };
            $message = implode(', ', array_map($callback, $names))
                . ' warmer(s) have been put into <info>%s</info> index.';
        }

        $output->writeln(sprintf($message, $input->getOption('connection')));

        return 0;
    }
}
