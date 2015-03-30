<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Result\Suggestion;

use Ongr\ElasticsearchBundle\Result\Suggestion\SuggestionIterator;

/**
 * Unit tests for suggestion iterator.
 */
class SuggestionIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if offset get returns null if it's not set.
     */
    public function testOffsetGet()
    {
        $iterator = new SuggestionIterator([]);
        $this->assertNull($iterator['test']);
    }

    /**
     * Check if offset set throws an exception.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Data of this iterator can not be changed after initialization.
     */
    public function testOffsetUnset()
    {
        $highlight = new SuggestionIterator([]);
        $highlight->offsetUnset('test');
    }

    /**
     * Check if offset set throws an exception.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Data of this iterator can not be changed after initialization.
     */
    public function testOffsetSet()
    {
        $highlight = new SuggestionIterator([]);
        $highlight->offsetSet('test', 'test');
    }
}
