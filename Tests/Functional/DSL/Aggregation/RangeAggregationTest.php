<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Aggregation;

use ONGR\ElasticsearchBundle\DSL\Aggregation\RangeAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class RangeAggregationTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 1,
                        'title' => 'foo',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'pizza',
                        'price' => 15.1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testRangeAggregation.
     *
     * @return array
     */
    public function getRangeAggregationData()
    {
        $out = [];

        // Case #0 single range aggregation.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'price',
            'range' => [
                'from' => '10',
                'to' => 20,
            ],
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => '10.0-20.0',
                        'from' => 10,
                        'from_as_string' => '10.0',
                        'to' => 20,
                        'to_as_string' => '20.0',
                        'doc_count' => 2,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        // Case #1 nested range aggregations.

        $aggregation2 = [
            'name' => 'test_agg',
            'field' => 'price',
            'range' => [
                'from' => '10',
                'to' => 20,
            ],
            'agg' => [
                'name' => 'test_agg2',
                'keyed' => true,
                'key' => 'test_keyed_range',
                'range' => [
                    'from' => 15,
                    'to' => null,
                ],
            ],
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => '10.0-20.0',
                        'from' => 10,
                        'from_as_string' => '10.0',
                        'to' => 20,
                        'to_as_string' => '20.0',
                        'doc_count' => 2,
                        'agg_test_agg2' => [
                            'buckets' => [
                                'test_keyed_range' => [
                                    'from' => 15,
                                    'from_as_string' => '15.0',
                                    'doc_count' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation2,
            $result,
        ];

        return $out;
    }

    /**
     * Test for range aggregation.
     *
     * @param array $aggregation
     * @param array $expectedResult
     *
     * @dataProvider getRangeAggregationData
     */
    public function testRangeAggregation($aggregation, $expectedResult)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $rangeAggregation = new RangeAggregation($aggregation['name']);
        $rangeAggregation->setField($aggregation['field']);
        $rangeAggregation->addRange($aggregation['range']['from'], $aggregation['range']['to']);

        if (!empty($aggregation['agg'])) {
            $childAgg = $aggregation['agg'];
            $aggregation2 = new RangeAggregation($childAgg['name']);
            $aggregation2->setKeyed($childAgg['keyed']);
            $aggregation2->addRange($childAgg['range']['from'], $childAgg['range']['to'], $childAgg['key']);

            $rangeAggregation->aggregations->add($aggregation2);
        }

        $search = $repo->createSearch()->addAggregation($rangeAggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResult, $results['aggregations']);
    }
}
