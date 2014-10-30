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

use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;

class ValueAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testGetValue().
     *
     * @return array
     */
    public function getTestGetValueData()
    {
        $cases = [];

        // Case #0 Simple aggregation.
        $cases[] = [
            ['doc_count' => 15],
            ['doc_count' => 15],
        ];

        // Case #1 Nested aggregation.
        $cases[] = [
            [
                'doc_count' => 15,
                'agg_sub-aggregation' => [
                    'doc_count' => 1,
                ],
            ],
            ['doc_count' => 15],
        ];

        // Case #2 Aggregation without value.
        $cases[] = [
            [
                'agg_sub-aggregation' => [
                    'doc_count' => 1,
                ],
            ],
            [],
        ];

        return $cases;
    }

    /**
     * Test for getValue().
     *
     * @param array $rawData
     * @param array $expectedResult
     *
     * @dataProvider getTestGetValueData()
     */
    public function testGetValue($rawData, $expectedResult)
    {
        $aggregation = new ValueAggregation($rawData);

        $this->assertEquals($expectedResult, $aggregation->getValue());
    }

    /**
     * Test for getValue() in case value is already cached.
     */
    public function testGetValueCached()
    {
        $rawData = ['doc_count' => 15];
        $expectedResult = ['doc_count' => 15];

        $aggregation = new ValueAggregation($rawData);
        $this->assertEquals($expectedResult, $aggregation->getValue());

        // Check if cached version is the same.
        $this->assertEquals($expectedResult, $aggregation->getValue());
    }

    /**
     * Test for getAggregations().
     */
    public function testGetAggregations()
    {
        $rawData = [
            'doc_count' => 15,
            'agg_foo' => [
                'doc_count' => 1,
            ],
            'agg_bar' => [
                'doc_count' => 2,
            ],
        ];

        $expectedResult = [
            'foo' => new ValueAggregation($rawData['agg_foo']),
            'bar' => new ValueAggregation($rawData['agg_bar']),
        ];

        $aggregation = new ValueAggregation($rawData);
        $aggregations = $aggregation->getAggregations();

        $this->assertEquals($expectedResult, iterator_to_array($aggregations));
    }

    /**
     * Test for getAggregations() in case aggregations are already cached.
     */
    public function testGetAggregationsCached()
    {
        $rawData = [
            'doc_count' => 15,
            'agg_foo' => [
                'doc_count' => 1,
            ],
        ];

        $aggregation = new ValueAggregation($rawData);
        $this->assertEquals($aggregation->getAggregations(), $aggregation->getAggregations());
    }
}
