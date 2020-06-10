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

class BulkEvent extends BaseEvent
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
     * @param string       $operation
     * @param string|array $type
     * @param array        $query
     */
    public function __construct($operation, $type, array $query)
    {
        $this->type = $type;
        $this->query = $query;
        $this->operation = $operation;
    }

    /**
     * @return array|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array|string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }
}
