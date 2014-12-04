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
use ONGR\ElasticsearchBundle\Test\TestHelperTrait;

class TermsAggregationTest extends ElasticsearchTestCase
{
    use TestHelperTrait;

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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');

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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setSize(1);

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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setOrder(TermsAggregation::MODE_TERM, TermsAggregation::DIRECTION_ASC);

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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setMinDocumentCount(2);

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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setInclude('sol.*');

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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setExclude('sol.*');

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
     * Data provider for testTermsAggregationWithRangeQuery.
     *
     * @return array
     */
    public function getTermsAggregationDataWithRangeQuery()
    {
        $out = [];

        // Case #6 terms aggregation with zero minimum document count.
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setMinDocumentCount(0);

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
        $repo = $this->getManager()->getRepository('ONGRTestingBundle:Product');

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArrayContainsArray($expectedResult, $results['aggregations']);
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
        $repo = $this->getManager()->getRepository('ONGRTestingBundle:Product');

        $rangeQuery = new RangeQuery('price', $parameters);

        $search = $repo->createSearch()->addQuery($rangeQuery)->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArrayContainsArray($expectedResult, $results['aggregations']);
    }
}
