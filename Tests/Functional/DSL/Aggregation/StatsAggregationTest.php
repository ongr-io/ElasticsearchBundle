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

use ONGR\ElasticsearchBundle\DSL\Aggregation\StatsAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class StatsAggregationTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.5.0', '>=', 'testStatsAggregationWithOlderResults'],
            ['1.5.0', '<', 'testStatsAggregation'],
            ['1.5.0', '<', 'testStatsAggregationWithScriptSet'],
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
     * Test for stats aggregation.
     */
    public function testStatsAggregation()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new StatsAggregation('test_agg');
        $aggregation->setField('price');

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);

        array_walk_recursive(
            $results,
            function (&$value, $key) {
                switch (gettype($value)) {
                    case 'string':
                        $value = is_numeric($value) ? (string)round(floatval($value), 2) : $value;
                        break;
                    case 'double':
                        $value = round($value, 2);
                        break;
                    default:
                        // Do nothing.
                        break;
                }
            }
        );

        $this->assertEquals(
            [
                'agg_test_agg' => [
                    'count' => 3,
                    'min' => 10.45,
                    'max' => 32,
                    'avg' => 19.18,
                    'sum' => 57.55,
                    'min_as_string' => '10.45',
                    'max_as_string' => '32',
                    'avg_as_string' => '19.18',
                    'sum_as_string' => '57.55',
                ],
            ],
            $results['aggregations']
        );
    }

    /**
     * Test for stats aggregation with older results.
     */
    public function testStatsAggregationWithOlderResults()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new StatsAggregation('test_agg');
        $aggregation->setField('price');

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $expectedResult = [
            'agg_test_agg' => [
                'count' => 3,
                'min' => 10.45,
                'max' => 32,
                'sum' => 57.55,
                'avg' => 19.18,
            ],
        ];

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResult, $results['aggregations'], '', 0.01);
    }

    /**
     * Test for stats aggregation when script is set.
     */
    public function testStatsAggregationWithScriptSet()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new StatsAggregation('test_agg');
        $aggregation->setField('price');
        $aggregation->setScript('_value * 1.2');

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $expectedResult = [
            'agg_test_agg' => [
                'count' => 3,
                'min' => 12.539999771118163,
                'max' => 38.4,
                'sum' => 69.060000228881833,
                'avg' => 23.020000076293943,
                'min_as_string' => '12.539999771118163',
                'max_as_string' => '38.4',
                'avg_as_string' => '23.020000076293943',
                'sum_as_string' => '69.06000022888183',
            ],
        ];
        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResult, $results['aggregations'], '', 0.01);
    }
}
