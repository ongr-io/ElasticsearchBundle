<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\EventListener;

use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\DependencyInjection\Container;

class TerminateListener
{
    private $container;
    private $indexes;

    /**
     * @param Container      $container
     * @param IndexService[] $indexes
     */
    public function __construct(Container $container, array $indexes)
    {
        $this->container = $container;
        $this->indexes = $indexes;
    }

    /**
     * Forces commit to the elasticsearch on kernel terminate event
     */
    public function onKernelTerminate()
    {
        foreach ($this->indexes as $key => $index) {
            /** @var IndexService $index */
            $index = $this->container->get($index);
            $index->commit();
        }
    }
}
