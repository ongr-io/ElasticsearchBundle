<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Event;

use ONGR\ElasticsearchBundle\Service\Manager;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event to be dispatched in various Elasticsearch methods.
 */
abstract class AbstractElasticsearchEvent extends Event
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Returns manager associated with the event.
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
