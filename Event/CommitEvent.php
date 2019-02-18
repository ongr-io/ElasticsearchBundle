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

class CommitEvent extends Event
{
    private $commitMode;
    private $bulkQuery;
    private $bulkResponse;

    public function __construct(string $commitMode, array $bulkQuery = [], array $bulkResponse = [])
    {
        $this->commitMode = $commitMode;
        $this->bulkQuery = $bulkQuery;
        $this->bulkResponse = $bulkResponse;
    }

    public function getCommitMode()
    {
        return $this->commitMode;
    }

    public function setCommitMode($commitMode)
    {
        $this->commitMode = $commitMode;
        return $this;
    }

    public function getBulkQuery(): array
    {
        return $this->bulkQuery;
    }

    public function setBulkQuery(array $bulkQuery): CommitEvent
    {
        $this->bulkQuery = $bulkQuery;
        return $this;
    }

    public function getBulkResponse(): array
    {
        return $this->bulkResponse;
    }

    public function setBulkResponse(array $bulkResponse): CommitEvent
    {
        $this->bulkResponse = $bulkResponse;
        return $this;
    }
}
