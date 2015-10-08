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
use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;

class ValueAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testGetValue().
     *
     * @return array
     */
    public function getTestGetValueData()
    {
        $aggPrefix = AbstractAggregation::PREFIX;
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
                $aggPrefix.'sub-aggregation' => ['doc_count' => 1],
            ],
            ['doc_count' => 15],
        ];

        // Case #2 Aggregation without value.
        $cases[] = [
            [
                $aggPrefix.'sub-aggregation' => ['doc_count' => 1],
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
        $aggPrefix = AbstractAggregation::PREFIX;
        $rawData = [
            'doc_count' => 15,
            $aggPrefix.'foo' => ['doc_count' => 1],
            $aggPrefix.'bar' => ['doc_count' => 2],
        ];

        $expectedResult = [
            $aggPrefix.'foo' => new ValueAggregation($rawData[$aggPrefix.'foo']),
            $aggPrefix.'bar' => new ValueAggregation($rawData[$aggPrefix.'bar']),
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
        $aggPrefix = AbstractAggregation::PREFIX;
        $rawData = [
            'doc_count' => 15,
            $aggPrefix.'foo' => ['doc_count' => 1],
        ];

        $aggregation = new ValueAggregation($rawData);
        $this->assertEquals($aggregation->getAggregations(), $aggregation->getAggregations());
    }
}
