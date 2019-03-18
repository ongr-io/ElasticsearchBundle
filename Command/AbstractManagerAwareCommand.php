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

use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * AbstractElasticsearchCommand class.
 */
abstract class AbstractManagerAwareCommand extends ContainerAwareCommand
{
    CONST INDEX_OPTION = 'index';

    protected function configure()
    {
        $this->addOption(
            self::INDEX_OPTION,
            'i',
            InputOption::VALUE_REQUIRED,
            'ElasticSearch index alias name or index name if you don\'t use aliases.'
        );
    }

    protected function getIndex($name): IndexService
    {
        $name = $name ?? $this->getContainer()->getParameter(Configuration::ONGR_DEFAULT_INDEX);
        $indexes = $this->getContainer()->getParameter(Configuration::ONGR_INDEXES);

        if (isset($indexes[$name]) && $this->getContainer()->has($indexes[$name])) {
            return $this->getContainer()->get($indexes[$name]);
        }

        throw new \RuntimeException(
            sprintf(
                'There is no index under `%s` name found. Available options: `%s`.',
                $name,
                implode('`, `', array_keys($this->getContainer()->getParameter(Configuration::ONGR_INDEXES)))
            )
        );
    }
}
