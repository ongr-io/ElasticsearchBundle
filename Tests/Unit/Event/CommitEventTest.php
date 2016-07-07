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

use ONGR\ElasticsearchBundle\Event\CommitEvent;

class CommitEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $event = new CommitEvent('flush', []);

        $this->assertEquals('flush', $event->getCommitMode());
        $this->assertEquals([], $event->getBulkParams());
    }
}
