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

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class DocumentIteratorAggregationsTest extends ElasticsearchTestCase
{
    /**
     * Data provider for testIteration().
     *
     * @return array
     */
    public function getTestIterationData()
    {
        // Case 0: test with aggregation.
        $rawData = [
            'aggregations' => [
                'agg_test_agg' => [
                    'buckets' => [
                        ['value' => 'test'],
                    ],
                ],
            ],
        ];
        $cases[] = [
            $rawData,
            [
                ['value' => 'test'],
            ],
        ];

        // Case 1: test with empty rawData.
        $rawData = [];
        $cases[] = [
            $rawData,
            [],
        ];

        return $cases;
    }

    /**
     * Iteration test.
     *
     * @param array $rawData
     * @param array $expected
     *
     * @dataProvider getTestIterationData
     */
    public function testIteration($rawData, $expected)
    {
        $documentsIterator = new DocumentIterator($rawData, [], []);
        $aggregations = $documentsIterator->getAggregations();

        $values = [];
        foreach ($aggregations as $bucket) {
            foreach ($bucket as $result) {
                $values[] = $result->getValue();
            }
        }

        $this->assertEquals($expected, $values);
    }
}
