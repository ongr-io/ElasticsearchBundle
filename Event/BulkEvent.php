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
    private $type;
    private $header;
    private $query;

    public function __construct($operation, $type, array $header, array $query)
    {
        $this->type = $type;
        $this->header = $header;
        $this->query = $query;
        $this->operation = $operation;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function setHeader(array $header): void
    {
        $this->header = $header;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function setQuery(array $query): void
    {
        $this->query = $query;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation)
    {
        $this->operation = $operation;
    }
}
