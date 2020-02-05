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
use PHPUnit\Framework\TestCase;

class CommitEventTest extends TestCase
{
    public function testGetters()
    {
        $query = [
            [
            '_index' => 'index',
            '_id' => 10,
            ],
            [
                'title' => 'bar'
            ]
        ];

        $response = ['status' => 'ok'];

        $event = new CommitEvent('flush', $query, $response);

        $this->assertEquals('flush', $event->getCommitMode());
        $this->assertEquals($query, $event->getBulkQuery());
        $this->assertEquals('flush', $event->getCommitMode());
        $this->assertEquals($response, $event->getBulkResponse());
    }
}
