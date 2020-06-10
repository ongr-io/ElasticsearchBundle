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

class CommitEvent extends BaseEvent
{
    /**
     * @var string
     */
    private $commitMode;

    /**
     * @var array
     */
    private $bulkParams;

    /**
     * @param string $commitMode
     * @param array|null  $bulkParams BulkQueries or BulkResponse, depending on event
     */
    public function __construct($commitMode, $bulkParams = [])
    {
        $this->commitMode = $commitMode;
        $this->bulkParams = $bulkParams;
    }

    /**
     * Returns commit mode
     *
     * @return string
     */
    public function getCommitMode()
    {
        return $this->commitMode;
    }

    /**
     * @param string $commitMode
     */
    public function setCommitMode($commitMode)
    {
        $this->commitMode = $commitMode;
    }

    /**
     * Returns params
     *
     * @return array
     */
    public function getBulkParams()
    {
        return $this->bulkParams;
    }

    /**
     * @param array $bulkParams
     */
    public function setBulkParams($bulkParams)
    {
        $this->bulkParams = $bulkParams;
    }
}
