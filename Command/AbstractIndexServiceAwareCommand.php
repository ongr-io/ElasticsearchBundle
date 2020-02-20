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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractIndexServiceAwareCommand extends Command
{
    private $container;

    const INDEX_OPTION = 'index';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

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
        $name = $name ?? $this->container->getParameter(Configuration::ONGR_DEFAULT_INDEX);
        $indexes = $this->container->getParameter(Configuration::ONGR_INDEXES);

        if (isset($indexes[$name]) && $this->container->has($indexes[$name])) {
            return $this->container->get($indexes[$name]);
        }

        throw new \RuntimeException(
            sprintf(
                'There is no index under `%s` name found. Available options: `%s`.',
                $name,
                implode('`, `', array_keys($this->container->getParameter(Configuration::ONGR_INDEXES)))
            )
        );
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
