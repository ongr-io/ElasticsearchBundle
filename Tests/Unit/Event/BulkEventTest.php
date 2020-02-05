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

use ONGR\ElasticsearchBundle\Event\BulkEvent;
use PHPUnit\Framework\TestCase;

class BulkEventTest extends TestCase
{
    public function testGetters()
    {
        $operation = 'create';
        $header = [
            '_index' => 'index',
            '_id' => 15,
        ];

        $expectedHeader = [
            '_index' => 'index',
            '_id' => 10,
        ];

        $data = [];

        $event = new BulkEvent($operation, $header, $data);
        $this->assertEquals('create', $event->getOperation());
        $event->setOperation('update');
        $this->assertEquals('update', $event->getOperation());

        $event->setHeader($expectedHeader);
        $this->assertEquals($expectedHeader, $event->getHeader());
        $this->assertEquals([], $event->getData());
        $event->setData($expectedHeader);
        $this->assertEquals($expectedHeader, $event->getData());
    }
}
