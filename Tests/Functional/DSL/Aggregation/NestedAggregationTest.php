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

use ONGR\ElasticsearchBundle\DSL\Aggregation\NestedAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\StatsAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class NestedAggregationTest extends ElasticsearchTestCase
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
                        'sub_products' => [
                            [
                                '_id' => 1,
                                'title' => 'foo',
                                'price' => 10,
                            ],
                            [
                                '_id' => 1,
                                'title' => 'baz',
                                'price' => 25,
                            ],
                        ],
                    ],
                    [
                        'sub_products' => [
                            [
                                '_id' => 2,
                                'title' => 'foo',
                                'price' => 100,
                            ],
                        ],
                    ],
                    [
                        'sub_products' => [
                            [
                                '_id' => 3,
                                'title' => 'bar',
                                'price' => 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testNestedAggregation.
     *
     * @return array
     */
    public function getNestedAggregationData()
    {
        $out = [];

        $mapping = [
            'product' => [
                'properties' => [
                    'sub_products' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ],
                            'title' => [
                                'type' => 'string',
                            ],
                            'price' => [
                                'type' => 'float',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Case #0 simple terms aggregation.
        $aggregation = new TermsAggregation('test_terms_agg');
        $aggregation->setField('sub_products.title');

        $result = [
            'doc_count' => 4,
            'agg_test_terms_agg' => [
                'buckets' => [
                    [
                        'key' => 'foo',
                        'doc_count' => 2,
                    ],
                    [
                        'key' => 'bar',
                        'doc_count' => 1,
                    ],
                    [
                        'key' => 'baz',
                        'doc_count' => 1,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
            $mapping,
        ];

        // Case #1 simple stats aggregation.
        $aggregation = new StatsAggregation('test_stats_agg');
        $aggregation->setField('sub_products.price');

        $result = [
            'doc_count' => 4,
            'agg_test_stats_agg' => [
                'count' => 4,
                'min' => 10,
                'max' => 1000,
                'sum' => 1135,
                'avg' => 283.75,
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
            $mapping,
        ];

        return $out;
    }

    /**
     * Test for nested terms aggregation.
     *
     * @param TermsAggregation $aggregation
     * @param array            $expectedResult
     * @param array            $mapping
     *
     * @dataProvider getNestedAggregationData
     */
    public function testNestedAggregation($aggregation, $expectedResult, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Product');

        $nestedAggregation = new NestedAggregation('test_nested_agg');
        $nestedAggregation->setPath('sub_products');

        $nestedAggregation->addAggregation($aggregation);

        $search = $repo->createSearch()->addAggregation($nestedAggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArraySubset($expectedResult, $results['aggregations']['agg_test_nested_agg']);
    }
}
