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
 * Functional tests for extended stats aggregation. Elasticsearch version >= 1.5.0.
 */
class ExtendedStatsAggregationTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.5.0', '<'],
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

        $expectedMin = 10.450;

        $this->assertArrayHasKey('aggregations', $results, 'results array should have aggregations key');
        $this->assertEquals($expectedMin, $results['aggregations'][$aggregation->getName()]['min'], '', 0.01);
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
            'count' => 3,
            'min' => 10.450,
            'max' => 32.0,
            'avg' => 19.183,
            'sum' => 57.550,
            'sum_of_squares' => 1361.212,
            'variance' => 85.737,
            'std_deviation' => 9.259,
            'std_deviation_bounds' => ['upper' => 28.443, 'lower' => 9.924],
        ];

        foreach ($expectedResult as $checkKey => $checkValue) {
            $this->assertEquals($checkValue, $results['aggregations']['agg_test_agg'][$checkKey], '', 0.01);
        }
    }

    /**
     * Test for extended stats aggregation with script set.
     */
    public function testExtendedStatsAggregationWithScriptSet()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new ExtendedStatsAggregation('test_agg');
        $aggregation->setScript("doc['product.price'].value * 1.5");
        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $expectedMin = 15.675;

        $this->assertEquals($expectedMin, $results['aggregations'][$aggregation->getName()]['min'], '', 0.01);
    }
}
