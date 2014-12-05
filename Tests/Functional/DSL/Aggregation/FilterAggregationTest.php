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

use ONGR\ElasticsearchBundle\DSL\Aggregation\FilterAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\StatsAggregation;
use ONGR\ElasticsearchBundle\DSL\Filter\RegexpFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class FilterAggregationTest extends ElasticsearchTestCase
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
     * Data provider for testFilterAggregation().
     *
     * @return array
     */
    public function getFilterAggregationData()
    {
        $out = [];

        // Case #0 Single filter aggregation.
        $aggregation = new FilterAggregation('test_agg');
        $filter = new RegexpFilter('title', 'pizza');
        $aggregation->setFilter($filter);

        $result = [
            'agg_test_agg' => [
                'doc_count' => 1,
            ],
        ];

        $out[] = [$aggregation, $result];

        // Case #1 Nested filter aggregation.
        $aggregation = new FilterAggregation('test_agg');
        $filter = new RegexpFilter('title', 'pizza');
        $aggregation->setFilter($filter);

        $aggregation2 = new StatsAggregation('test_agg_2');
        $aggregation2->setField('price');
        $aggregation->aggregations->addAggregation($aggregation2);

        $result = [
            'agg_test_agg' => [
                'doc_count' => 1,
                'agg_test_agg_2' => [
                    'count' => 1,
                    'min' => 15.10,
                    'max' => 15.10,
                    'avg' => 15.10,
                    'sum' => 15.10,
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

        return $out;
    }

    /**
     * Test for filter aggregation.
     *
     * @param Filter $aggregation
     * @param array  $expectedResults
     *
     * @dataProvider getFilterAggregationData
     */
    public function testFilterAggregation($aggregation, $expectedResults)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()->addAggregation($aggregation);

        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResults, $results['aggregations'], '', 0.001);
    }
}
