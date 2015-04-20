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

use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\DSL\Query\RangeQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class TermsAggregationTest extends ElasticsearchTestCase
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
                        'surface' => 'weak',
                        'title' => 'pizza',
                        'price' => 15.1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testTermsAggregation.
     *
     * @return array
     */
    public function getTermsAggregationData()
    {
        $out = [];

        // Case #0 simple terms aggregation.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'weak',
                        'doc_count' => 2,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        // Case #1 terms aggregation with limited size.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
            'size' => 1,
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'weak',
                        'doc_count' => 2,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        // Case #2 terms aggregation with custom ordering.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
            'order' => [TermsAggregation::MODE_TERM, TermsAggregation::DIRECTION_ASC],
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'solid',
                        'doc_count' => 1,
                    ],
                    [
                        'key' => 'weak',
                        'doc_count' => 2,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        // Case #3 terms aggregation with minimum document count.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
            'min_document_cound' => 2,
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'weak',
                        'doc_count' => 2,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        // Case #4 terms aggregation with include.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
            'include' => 'sol.*',
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'solid',
                        'doc_count' => 1,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        // Case #5 terms aggregation with exclude.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
            'exclude' => 'sol.*',
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'weak',
                        'doc_count' => 2,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            $result,
        ];

        return $out;
    }

    /**
     * Test for terms aggregation.
     *
     * @param TermsAggregation $aggregation
     * @param array            $expectedResult
     *
     * @dataProvider getTermsAggregationData
     */
    public function testTermsAggregation($aggregation, $expectedResult)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()->addAggregation($this->getAggregation($aggregation));
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArraySubset($expectedResult, $results['aggregations']);
    }

    /**
     * Data provider for testTermsAggregationWithRangeQuery.
     *
     * @return array
     */
    public function getTermsAggregationDataWithRangeQuery()
    {
        $out = [];

        // Case #6 terms aggregation with zero minimum document count.
        $aggregation = [
            'name' => 'test_agg',
            'field' => 'surface',
            'min_document_count' => 0,
        ];

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    [
                        'key' => 'weak',
                        'doc_count' => 2,
                    ],
                    [
                        'key' => 'solid',
                        'doc_count' => 0,
                    ],
                ],
            ],
        ];

        $out[] = [
            $aggregation,
            [RangeQuery::GT => 15],
            $result,
        ];

        return $out;
    }

    /**
     * Test for terms aggregation with range query and zero min_doc_count.
     *
     * @param TermsAggregation $aggregation
     * @param array            $parameters
     * @param array            $expectedResult
     *
     * @dataProvider getTermsAggregationDataWithRangeQuery
     */
    public function testTermsAggregationWithRangeQuery($aggregation, $parameters, $expectedResult)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $rangeQuery = new RangeQuery('price', $parameters);

        $search = $repo->createSearch()->addQuery($rangeQuery)->addAggregation($this->getAggregation($aggregation));
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArraySubset($expectedResult, $results['aggregations']);
    }

    /**
     * Builds term aggregation.
     *
     * @param array $options
     *
     * @return TermsAggregation
     */
    private function getAggregation($options)
    {
        $term = new TermsAggregation($options['name']);
        $term->setField($options['field']);

        if (array_key_exists('exclude', $options)) {
            $term->setExclude($options['exclude']);
        }

        if (array_key_exists('include', $options)) {
            $term->setInclude($options['include']);
        }

        if (array_key_exists('min_document_count', $options)) {
            $term->setMinDocumentCount($options['min_document_count']);
        }

        if (array_key_exists('order', $options)) {
            call_user_func_array([$term, 'setOrder'], $options['order']);
        }

        if (array_key_exists('size', $options)) {
            $term->setSize($options['size']);
        }

        return $term;
    }
}
