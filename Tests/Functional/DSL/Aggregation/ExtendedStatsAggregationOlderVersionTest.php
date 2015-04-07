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

use ONGR\ElasticsearchBundle\DSL\Aggregation\ExtendedStatsAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for extended stats aggregation. Elasticsearch version < 1.5.0.
 */
class ExtendedStatsAggregationOlderVersionTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.4.3', '<'],
            ['1.5.0', '>='],
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
     * Test for extended stats aggregation.
     */
    public function testExtendedStatsAggregation()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new ExtendedStatsAggregation('test_agg');
        $aggregation->setField('price');

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $expectedResult = [
            'agg_test_agg' => [
                'count' => 3,
                'min' => 10.45,
                'max' => 32,
                'avg' => 19.18,
                'sum' => 57.55,
                'sum_of_squares' => 1361.21,
                'variance' => 85.74,
                'std_deviation' => 9.26,
                'std_deviation_bounds' => ['upper' => 37.7, 'lower' => 0.66],
            ],
        ];

        $this->assertArrayHasKey('aggregations', $results, 'results array should have aggregations key');
        $this->assertEquals($expectedResult, $results['aggregations'], '', 0.01);
    }

    /**
     * Test for extended stats aggregation with sigma set.
     */
    public function testExtendedStatsAggregationWithSigmaSet()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new ExtendedStatsAggregation('test_agg');
        $aggregation->setField('price');
        $aggregation->setSigma(1);

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $expectedResult = [
            'agg_test_agg' => [
                'count' => 3,
                'min' => 10.45,
                'max' => 32,
                'avg' => 19.18,
                'sum' => 57.55,
                'sum_of_squares' => 1361.21,
                'variance' => 85.74,
                'std_deviation' => 9.26,
                'std_deviation_bounds' => ['upper' => 28.44, 'lower' => 9.92],
            ],
        ];

        $this->assertEquals($expectedResult, $results['aggregations'], '', 0.01);
    }

    /**
     * Test for extended stats aggregation with script.
     */
    public function testExtendedStatsAggregationWithScriptSet()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new ExtendedStatsAggregation('test_agg');
        $aggregation->setScript("doc['product.price'].value * 1.5");
        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $expectedResult = [
            'agg_test_agg' => [
                'count' => 3,
                'min' => 15.67,
                'max' => 48,
                'avg' => 28.78,
                'sum' => 86.33,
                'sum_of_squares' => 3062.73,
                'variance' => 192.91,
                'std_deviation' => 13.89,
                'std_deviation_bounds' => ['upper' => 56.55, 'lower' => 0.99],
            ],
        ];

        $this->assertEquals($expectedResult, $results['aggregations'], '', 0.01);
    }
}
