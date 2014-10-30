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

use ONGR\ElasticsearchBundle\Result\Suggestions;

class SuggestionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns sample data for tests.
     *
     * @return array
     */
    protected function getTestData()
    {
        return [
            'foo' => [
                'text' => 'foobar',
                'offset' => 0,
                'length' => 6,
                'options' => [
                    [
                        'text' => 'foobar',
                        'freq' => 77,
                        'score' => 0.8888889,
                    ],
                ],
            ],
            'bar' => [
                'text' => 'barbaz',
                'offset' => 0,
                'length' => 6,
                'options' => [],
            ],
        ];
    }

    /**
     * Test for \ArrayAccess interface implementation.
     */
    public function testArrayAccess()
    {
        $iterator = new Suggestions($this->getTestData());

        $this->assertEquals($this->getTestData()['foo'], $iterator->offsetGet('foo'));
        $this->assertEquals($this->getTestData()['bar'], $iterator->offsetGet('bar'));

        // Should return NULL if key does not exist.
        $this->assertNull($iterator->offsetGet('baz'));
    }

    /**
     * Test for offsetSet().
     *
     * @expectedException \LogicException
     */
    public function testOffsetSet()
    {
        $iterator = new Suggestions([]);
        $iterator->offsetSet('foo', 'bar');
    }

    /**
     * Test for offsetUnset().
     *
     * @expectedException \LogicException
     */
    public function testOffsetUnset()
    {
        $iterator = new Suggestions([]);
        $iterator->offsetUnset('foo');
    }
}
