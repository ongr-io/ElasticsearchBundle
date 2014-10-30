<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Result;

use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class DocumentIteratorNestedAggregationTest extends ElasticsearchTestCase
{
    /**
     * Data provider for testIteration().
     *
     * @return array
     */
    public function getTestIterationData()
    {
        $cases = [];

        $rawData = [
            'foo' => [
                'doc_count' => 1,
            ],
            'bar' => [
                'doc_count' => 2,
            ],
        ];
        $expected = new ValueAggregation($rawData['foo']);
        $cases[] = ['foo', $rawData, $expected];

        $rawData = [
            'foo' => [
                'buckets' => [
                    'bucket_1' => ['doc_count' => 1],
                ],
            ],
            'bar' => [
                'doc_count' => 2,
            ],
        ];
        $expected = new AggregationIterator($rawData['foo']['buckets']);
        $cases[] = ['foo', $rawData, $expected];

        $rawData = [
            'foo' => [
                'buckets' => [
                    'bucket_1' => ['doc_count' => 1],
                ],
            ],
            'bar' => [
                'doc_count' => 2,
            ],
        ];
        $expected = new ValueAggregation($rawData['foo']['buckets']['bucket_1']);
        $cases[] = ['foo.bucket_1', $rawData, $expected];

        return $cases;
    }

    /**
     * Iteration test.
     *
     * @param string $path
     * @param array  $rawData
     * @param array  $expected
     *
     * @dataProvider getTestIterationData
     */
    public function testIteration($path, $rawData, $expected)
    {
        $this->assertEquals($expected, (new AggregationIterator($rawData))->find($path));
    }
}
