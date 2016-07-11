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

use Symfony\Component\EventDispatcher\Event;

class OperationEvent extends Event
{
    /**
     * @var string
     */
    private $operation;

    /**
     * @var string|array
     */
    private $type;

    /**
     * @var array
     */
    private $query;

    /**
     * Constructor
     *
     * @param string       $operation
     * @param string|array $type
     * @param array        $query
     */
    public function __construct($operation, $type, array $query)
    {
        $this->operation = $operation;
        $this->type = $type;
        $this->query = $query;
    }

    /**
     * Returns operation
     *
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Returns type
     *
     * @return array|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns query
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }
}
