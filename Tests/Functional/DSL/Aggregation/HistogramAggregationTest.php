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

use ONGR\ElasticsearchBundle\DSL\Aggregation\HistogramAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\StatsAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for histogram aggregation.
 */
class HistogramAggregationTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.5.0', '<', 'testHistogramAggregationWithOrderAndMinDocCountSet'],
        ];
    }

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
                        'price' => 2,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 3,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'pizza',
                        'price' => 7,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testHistogramAggregation().
     *
     * @return array
     */
    public function getHistogramAggregationData()
    {
        $out = [];

        // Case #0 simple histogram aggregation test with keyed set.
        $aggregationData = array_filter(
            [
                'order' => null,
                'min_doc_count' => null,
                'extended_bounds' => null,
                'keyed' => true,
            ]
        );
        $expectedResults = [
            'agg_test_agg' => [
                'buckets' => [
                    0 => ['key' => 0, 'doc_count' => 2],
                    5 => ['key' => 5, 'doc_count' => 1],
                ],
            ],
        ];
        $out[] = [$aggregationData, $expectedResults];

        // Case #1 test histogram aggregation with extended_bounds.
        $aggregationData = array_filter(
            [
                'order' => null,
                'min_doc_count' => null,
                'extended_bounds' => [
                    'min' => 2,
                    'max' => 4,
                ],
                'keyed' => false,
            ]
        );
        $expectedResults = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 0,
                        'doc_count' => 2,
                    ],
                    [
                        'key' => 5,
                        'doc_count' => 1,
                    ],
                ],
            ],
        ];
        $out[] = [$aggregationData, $expectedResults];

        return $out;
    }

    /**
     * Test histogram aggregation for expected search results.
     *
     * @param array $aggregationData
     * @param array $expectedResults
     *
     * @dataProvider getHistogramAggregationData
     */
    public function testHistogramAggregation($aggregationData, $expectedResults)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new HistogramAggregation('test_agg');
        $aggregation->setField('price');
        $aggregation->setInterval(5);

        if (array_key_exists('min_doc_count', $aggregationData)) {
            $aggregation->setMinDocCount($aggregationData['min_doc_count']);
        }

        if (array_key_exists('extended_bounds', $aggregationData)) {
            $aggregation->setExtendedBounds(
                $aggregationData['extended_bounds']['min'],
                $aggregationData['extended_bounds']['max']
            );
        }

        if (array_key_exists('keyed', $aggregationData)) {
            $aggregation->setKeyed($aggregationData['keyed']);
        }

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW)['aggregations'];

        $this->assertEquals($expectedResults, $results);
    }

    /**
     * Test for histogram aggregation. Order and min doc count set. Elasticsearch version >= 1.5.0.
     */
    public function testHistogramAggregationWithOrderAndMinDocCountSet()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new HistogramAggregation('test_agg');
        $aggregation->setField('price');
        $aggregation->setInterval(5);
        $aggregation->setMinDocCount(2);

        $statsAggregation = new StatsAggregation('price_stats');
        $statsAggregation->setField('price');

        $aggregation->setOrder($statsAggregation->getName() . '.min', HistogramAggregation::DIRECTION_ASC);
        $aggregation->addAggregation($statsAggregation);
        $expectedResults = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 0,
                        'doc_count' => 2,
                        'agg_price_stats' => [
                            'count' => 2,
                            'min' => 2.0,
                            'max' => 3.0,
                            'avg' => 2.5,
                            'sum' => 5.0,
                            'min_as_string' => '2.0',
                            'max_as_string' => '3.0',
                            'avg_as_string' => '2.5',
                            'sum_as_string' => '5.0',
                        ],
                    ],
                ],
            ],
        ];

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW)['aggregations'];

        $this->assertEquals($expectedResults, $results);
    }
}
