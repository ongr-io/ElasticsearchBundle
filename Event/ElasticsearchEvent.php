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

use ONGR\ElasticsearchBundle\Client\Connection;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event to be dispatched in various Elasticsearch methods.
 */
class ElasticsearchEvent extends Event
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns connection associated with the event.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
