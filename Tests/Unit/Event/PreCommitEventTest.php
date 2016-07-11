<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Event;

use ONGR\ElasticsearchBundle\Event\PreCommitEvent;

class PreCommitEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PreCommitEvent
     */
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->event = new PreCommitEvent('test', []);
    }

    public function testGetCommitMode()
    {
        $this->assertEquals('test', $this->event->getCommitMode());
    }

    public function testGetParams()
    {
        $this->assertEquals([], $this->event->getParams());
    }
}
