<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result;

use ONGR\ElasticsearchBundle\Result\ObjectCallbackIterator;

class ObjectCallbackIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the current method of the iterator uses the callback.
     */
    public function testCallbackIteratorCurrent()
    {
        $rawData = [null, null, null];

        $callback = function ($key) {
            return $key;
        };

        $iterator = new ObjectCallbackIterator($callback, $rawData);

        $this->assertEquals(0, $iterator->current());
    }

    /**
     * Tests the offsetGet method of the iterator uses the callback.
     */
    public function testCallbackIteratorOffsetGet()
    {
        $rawData = [null, null, null];

        $callback = function ($key) {
            return $key;
        };

        $iterator = new ObjectCallbackIterator($callback, $rawData);

        $this->assertEquals(1, $iterator->offsetGet(1));
    }

    /**
     * Tests iteration of the iterator uses the callback.
     */
    public function testCallbackIteratorIteration()
    {
        $rawData = [null, null, null];

        $callback = function ($key) {
            return 'foo';
        };

        $iterator = new ObjectCallbackIterator($callback, $rawData);

        foreach ($iterator as $item) {
            $this->assertEquals('foo', $item);
        }
    }

    /**
     * Tests iterator skips the callback for non-null values.
     */
    public function testCallbackIteratorUsesValue()
    {
        $rawData = [null, 'bar', null];

        $callback = function ($key) {
            return 'foo';
        };

        $iterator = new ObjectCallbackIterator($callback, $rawData);

        $this->assertEquals('bar', $iterator[1]);
    }
}
