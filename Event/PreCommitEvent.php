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

class PreCommitEvent extends Event
{
    /**
     * @var string
     */
    private $commitMode;

    /**
     * @var array
     */
    private $params;

    /**
     * Constructor
     *
     * @param string $commitMode
     * @param array  $params
     */
    public function __construct($commitMode, array $params = [])
    {
        $this->commitMode = $commitMode;
        $this->params = $params;
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
     * Returns params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
