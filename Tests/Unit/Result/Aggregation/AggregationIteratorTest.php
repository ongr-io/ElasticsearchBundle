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

/**
 * Class AggregationIteratorTest.
 */
class AggregationIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests countable interface implementation.
     */
    public function testCountable()
    {
        $this->assertInstanceOf('\\Countable', new AggregationIterator([]));
    }

    /**
     * Data provider for testCount.
     */
    public function countProvider()
    {
        $cases = [];

        // Case #0. No data.
        $cases[] = [
            'data' => [],
            'expectedCount' => 0,
        ];

        // Case #2. With data.
        $cases[] = [
            'data' => [
                [
                    'key' => 'weak',
                    'doc_count' => 2,
                    'agg_test_agg_2' => [
                        'buckets' => [
                            [
                                'key' => '*-20.0',
                                'to' => 20.0,
                                'to_as_string' => '20.0',
                                'doc_count' => 1,
                            ],
                            [
                                'key' => '20.0-*',
                                'from' => 20.0,
                                'from_as_string' => '20.0',
                                'doc_count' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'solid',
                    'doc_count' => 1,
                    'agg_test_agg_2' => [
                        'buckets' => [
                            [
                                'key' => '*-20.0',
                                'to' => 20.0,
                                'to_as_string' => '20.0',
                                'doc_count' => 1,
                            ],
                            [
                                'key' => '20.0-*',
                                'from' => 20.0,
                                'from_as_string' => '20.0',
                                'doc_count' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'expectedCount' => 2,
        ];

        return $cases;
    }

    /**
     * Tests counting.
     *
     * @dataProvider countProvider
     *
     * @param array $data
     * @param int   $expectedCount
     */
    public function testCount($data, $expectedCount)
    {
        $iterator = new AggregationIterator($data);
        $this->assertCount($expectedCount, $iterator);
    }
}
