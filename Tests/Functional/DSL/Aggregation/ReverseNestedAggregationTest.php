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
use ONGR\ElasticsearchBundle\DSL\Aggregation\ReverseNestedAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\FilterAggregation;
use ONGR\ElasticsearchBundle\DSL\Filter\TermFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class ReverseNestedAggregationTest extends AbstractElasticsearchTestCase
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
                        'name' => 'name-foo-baz',
                    ],
                    [
                        'sub_products' => [
                            [
                                '_id' => 2,
                                'title' => 'foo',
                                'price' => 100,
                            ],
                        ],
                        'name' => 'name-foo',
                    ],
                    [
                        'sub_products' => [
                            [
                                '_id' => 3,
                                'title' => 'bar',
                                'price' => 1000,
                            ],
                        ],
                        'name' => 'name-bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testReverseNestedAggregation.
     *
     * @return array
     */
    public function getReverseNestedAggregationData()
    {
        $out = [];

        $mapping = [
            'product' => [
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
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

        $reverseAggregation = new TermsAggregation('test_reverse_term_agg');
        $reverseAggregation->setField('name');

        $result = [
            'doc_count' => 4,
            'agg_test_terms_agg' => [
                'buckets' => [
                    [
                        'key' => 'foo',
                        'doc_count' => 2,
                        'agg_test_reverse_nested_agg' => [
                            'doc_count' => 2,
                            'agg_test_reverse_term_agg' => [
                                'buckets' => [
                                    [
                                        'key' => 'name-foo',
                                        'doc_count' => 1,
                                    ],
                                    [
                                        'key' => 'name-foo-baz',
                                        'doc_count' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'bar',
                        'doc_count' => 1,
                        'agg_test_reverse_nested_agg' => [
                            'doc_count' => 1,
                            'agg_test_reverse_term_agg' => [
                                'buckets' => [
                                    [
                                        'key' => 'name-bar',
                                        'doc_count' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'baz',
                        'doc_count' => 1,
                        'agg_test_reverse_nested_agg' => [
                            'doc_count' => 1,
                            'agg_test_reverse_term_agg' => [
                                'buckets' => [
                                    [
                                        'key' => 'name-foo-baz',
                                        'doc_count' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $reverseAggregation,
            $result,
            $mapping,
        ];

        // Case #1 simple filtred aggregation.
        $aggregation = new TermsAggregation('test_terms_agg');
        $aggregation->setField('sub_products.title');

        $termFilter = new TermFilter('name', 'name-foo');

        $reverseAggregation = new FilterAggregation('test_reverse_term_agg');
        $reverseAggregation->setFilter($termFilter);

        $result = [
            'doc_count' => 4,
            'agg_test_terms_agg' => [
                'buckets' => [
                    [
                        'key' => 'foo',
                        'doc_count' => 2,
                        'agg_test_reverse_nested_agg' => [
                            'doc_count' => 2,
                            'agg_test_reverse_term_agg' => [
                                'doc_count' => 1,
                            ],
                        ],
                    ],
                    [
                        'key' => 'bar',
                        'doc_count' => 1,
                        'agg_test_reverse_nested_agg' => [
                            'doc_count' => 1,
                            'agg_test_reverse_term_agg' => [
                                'doc_count' => 0,
                            ],
                        ],
                    ],
                    [
                        'key' => 'baz',
                        'doc_count' => 1,
                        'agg_test_reverse_nested_agg' => [
                            'doc_count' => 1,
                            'agg_test_reverse_term_agg' => [
                                'doc_count' => 0,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $reverseAggregation,
            $result,
            $mapping,
        ];

        return $out;
    }

    /**
     * Test for reverse nested terms aggregation.
     *
     * @param AbstractAggregation $aggregation
     * @param AbstractAggregation $reverseAggregation
     * @param array               $expectedResult
     * @param array               $mapping
     *
     * @dataProvider getReverseNestedAggregationData
     */
    public function testReverseNestedAggregation($aggregation, $reverseAggregation, $expectedResult, $mapping)
    {
        $repository = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Product');

        $revereNestedAggregation = new ReverseNestedAggregation('test_reverse_nested_agg');
        $revereNestedAggregation->addAggregation($reverseAggregation);

        $aggregation->addAggregation($revereNestedAggregation);

        $nestedAggregation = new NestedAggregation('test_nested_agg');
        $nestedAggregation->setPath('sub_products');
        $nestedAggregation->addAggregation($aggregation);

        $search = $repository->createSearch()->addAggregation($nestedAggregation);
        $results = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArraySubset($expectedResult, $results['aggregations']['agg_test_nested_agg']);
    }
}
