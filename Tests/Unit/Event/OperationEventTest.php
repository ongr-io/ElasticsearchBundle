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

use ONGR\ElasticsearchBundle\Event\OperationEvent;

class OperationEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OperationEvent
     */
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->event = new OperationEvent('test_operation', 'test_type', []);
    }

    public function testGetOperation()
    {
        $this->assertEquals('test_operation', $this->event->getOperation());
    }

    public function testGetType()
    {
        $this->assertEquals('test_type', $this->event->getType());
    }

    public function testGetQuery()
    {
        $this->assertEquals([], $this->event->getQuery());
    }
}
