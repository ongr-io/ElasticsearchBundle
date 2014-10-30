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

use ONGR\ElasticsearchBundle\DSL\Aggregation\StatsAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class AggregationIteratorFindTest extends ElasticsearchTestCase
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
                        'surface' => 'solid',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'surface' => 'weak',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'pizza',
                        'surface' => 'weak',
                        'price' => 15.1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testIteration.
     *
     * @return array
     */
    public function getTestIterationData()
    {
        $out = [];

        $rawData = [
            [
                'key' => 'weak',
                'doc_count' => 2,
                'agg_test_agg_2' => [
                    'count' => 2,
                    'min' => 15.1,
                    'max' => 32.0,
                    'avg' => 23.6,
                    'sum' => 47.1,
                ],
            ],
            [
                'key' => 'solid',
                'doc_count' => 1,
                'agg_test_agg_2' => [
                    'count' => 1,
                    'min' => 10.45,
                    'max' => 10.45,
                    'avg' => 10.45,
                    'sum' => 10.45,
                ],
            ],
        ];

        $expected = new AggregationIterator($rawData);

        $out[] = ['test_agg', $expected];

        $rawData = [
            'count' => 2,
            'min' => 15.1,
            'max' => 32.0,
            'avg' => 23.6,
            'sum' => 47.1,
        ];

        $expected = new ValueAggregation($rawData);

        $out[] = ['test_agg.0.test_agg_2', $expected];

        return $out;
    }

    /**
     * Aggregation test.
     *
     * @param string $path
     * @param array  $expected
     *
     * @dataProvider getTestIterationData
     */
    public function testIteration($path, $expected)
    {
        $aggregation = $this->buildAggregation();
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search);
        $result = $results->getAggregations()->find($path);

        $this->assertEquals($expected, $result, '', 0.1);
    }

    /**
     * Get aggregation collection with several aggregations registered.
     *
     * @return array
     */
    private function buildAggregation()
    {
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation2 = new StatsAggregation('test_agg_2');
        $aggregation2->setField('price');
        $aggregation->aggregations->addAggregation($aggregation2);

        return $aggregation;
    }
}
