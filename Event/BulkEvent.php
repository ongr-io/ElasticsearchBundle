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
    private $data;

    public function __construct(string $operation, array $header, array $data = [])
    {
        $this->operation = $operation;
        $this->header = $header;
        $this->data = $data;
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

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): BulkEvent
    {
        $this->data = $data;
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
