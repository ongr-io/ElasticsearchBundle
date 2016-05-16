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

use ONGR\ElasticsearchBundle\Service\Manager;
use Symfony\Component\DependencyInjection\Container;

class TerminateListener
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $managers;

    /**
     * Constructor
     *
     * @param Container $container
     * @param array     $managers
     */
    public function __construct(Container $container, array $managers)
    {
        $this->container = $container;
        $this->managers = $managers;
    }

    /**
     * Forces commit to elasticsearch on kernel terminate
     */
    public function onKernelTerminate()
    {
        foreach ($this->managers as $key => $value) {
            if ($value['force_commit']) {
                try {
                    /** @var Manager $manager */
                    $manager = $this->container->get(sprintf('es.manager.%s', $key));
                } catch (\Exception $e) {
                    continue;
                }
                $manager->commit();
            }
        }
    }
}
