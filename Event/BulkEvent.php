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

class BulkEvent extends Event
{
    private $operation;
    private $header;
    private $query;

    public function __construct(string $operation, array $header, array $query)
    {
        $this->header = $header;
        $this->query = $query;
        $this->operation = $operation;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function setHeader(array $header): BulkEvent
    {
        $this->header = $header;
        return $this;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function setQuery(array $query): BulkEvent
    {
        $this->query = $query;
        return $this;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): BulkEvent
    {
        $this->operation = $operation;
        return $this;
    }
}
