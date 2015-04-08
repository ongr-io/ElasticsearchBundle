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

use ONGR\ElasticsearchBundle\DSL\Aggregation\PercentilesAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for percentiles aggregation. Elasticsearch version < 1.5.0.
 */
class PercentilesAggregationOlderVersionTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
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
                        'price' => 10,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 15,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'bar',
                        'price' => 25,
                    ],
                    [
                        '_id' => 4,
                        'title' => 'bar',
                        'price' => 25,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testPercentilesAggregation().
     *
     * @return array
     */
    public function getPercentilesAggregationData()
    {
        $out = [];

        // Case #0 without any percent or compression.
        $aggregationData = ['field' => 'price', 'percents' => null, 'compression' => null];
        $expectedResults = [
            '1.0' => 10.15,
            '5.0' => 10.75,
            '25.0' => 13.75,
            '50.0' => 20,
            '75.0' => 25,
            '95.0' => 25,
            '99.0' => 25,
        ];
        $out[] = [$aggregationData, $expectedResults];

        // Case #1 with compression = 0.
        $aggregationData = ['field' => 'price', 'percents' => null, 'compression' => 0];
        $expectedResults = [
            '1.0' => 18.75,
            '5.0' => 18.75,
            '25.0' => 18.75,
            '50.0' => 18.75,
            '75.0' => 18.75,
            '95.0' => 18.75,
            '99.0' => 18.75,
        ];
        $out[] = [$aggregationData, $expectedResults];

        // Case #2 with percents.
        $aggregationData = ['field' => 'price', 'percents' => [10, 20, 90], 'compression' => 200];
        $expectedResults = [
            '10.0' => 11.5,
            '20.0' => 13,
            '90.0' => 25,
        ];
        $out[] = [$aggregationData, $expectedResults];

        return $out;
    }

    /**
     * Test for percentiles aggregation.
     *
     * @param array $aggData
     * @param array $expectedResults
     *
     * @dataProvider getPercentilesAggregationData()
     */
    public function testPercentilesAggregation($aggData, $expectedResults)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new PercentilesAggregation('test_agg');
        $aggregation->setField($aggData['field']);

        if (array_key_exists('compression', $aggData)) {
            $aggregation->setCompression($aggData['compression']);
        }
        if (array_key_exists('percents', $aggData)) {
            $aggregation->setPercents($aggData['percents']);
        }

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_OBJECT);

        /** @var ValueAggregation $result */
        $result = $results->getAggregations()['test_agg'];
        $this->assertEquals($expectedResults, $result->getValue()['values']);
    }

    /**
     * Tests percentiles aggregation using script instead of field.
     */
    public function testPercentilesAggregationWithScript()
    {
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new PercentilesAggregation('foo');
        $aggregation->setScript("doc['product.price'].value / 10");
        $search = $repository
            ->createSearch()
            ->addAggregation($aggregation);
        /** @var ValueAggregation $result */
        $result = $repository->execute($search)->getAggregations()->find('foo');
        $expectedResults = [
            '1.0' => 1.015,
            '5.0' => 1.075,
            '25.0' => 1.375,
            '50.0' => 2.0,
            '75.0' => 2.5,
            '95.0' => 2.5,
            '99.0' => 2.5,
        ];
        $this->assertEquals($expectedResults, $result->getValue()['values']);
    }
}
