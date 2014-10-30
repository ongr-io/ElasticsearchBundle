<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result\Aggregation;

use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;

class AggregationIteratorTest extends \PHPUnit_Framework_TestCase
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
                'doc_count' => 1,
            ],
            'bar' => [
                'doc_count' => 2,
            ],
        ];
    }

    /**
     * Test for \ArrayAccess interface implementation.
     */
    public function testArrayAccess()
    {
        $iterator = new AggregationIterator($this->getTestData());

        $this->assertEquals(new ValueAggregation(['doc_count' => 1]), $iterator->offsetGet('foo'));
        $this->assertEquals(new ValueAggregation(['doc_count' => 2]), $iterator->offsetGet('bar'));

        // Should return NULL if key does not exist.
        $this->assertNull($iterator->offsetGet('baz'));
    }

    /**
     * Test for \Iterator interface implementation.
     */
    public function testIterator()
    {
        $iterator = new AggregationIterator($this->getTestData());
        $iterator->rewind();

        $this->assertEquals('foo', $iterator->key());
        $this->assertEquals(new ValueAggregation(['doc_count' => 1]), $iterator->current());
        $iterator->next();

        $this->assertEquals('bar', $iterator->key());
        $this->assertEquals(new ValueAggregation(['doc_count' => 2]), $iterator->current());
    }

    /**
     * Test for nested aggregations structure.
     */
    public function testNestedAggregations()
    {
        $data = $this->getTestData();
        $data['bar']['agg_baz'] = ['doc_count' => 3];

        $iterator = new AggregationIterator($data);

        $this->assertTrue(isset($iterator['bar']->getAggregations()['baz']));
        $this->assertEquals(
            new ValueAggregation(['doc_count' => 3]),
            $iterator['bar']->getAggregations()['baz']
        );
    }

    /**
     * Test for bucketed aggregations structure.
     */
    public function testBucketedAggregations()
    {
        $data = [
            'foo' => [
                'buckets' => [
                    'bucket_1' => ['doc_count' => 1],
                ],
            ],
        ];

        $iterator = new AggregationIterator($data);

        $this->assertEquals(
            new ValueAggregation(['doc_count' => 1]),
            $iterator['foo']['bucket_1']
        );
    }

    /**
     * Test for offsetSet().
     *
     * @expectedException \LogicException
     */
    public function testOffsetSet()
    {
        $iterator = new AggregationIterator([]);
        $iterator->offsetSet('foo', 'bar');
    }

    /**
     * Test for offsetUnset().
     *
     * @expectedException \LogicException
     */
    public function testOffsetUnset()
    {
        $iterator = new AggregationIterator([]);
        $iterator->offsetUnset('foo');
    }
}
