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
use Symfony\Component\Console\Input\InputOption;

/**
 * Class AbstractWarmerCommand.
 */
abstract class AbstractWarmerCommand extends AbstractElasticsearchCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'names',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Warmers names.',
                []
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED,
                'Connection name.',
                'default'
            );
    }
}
