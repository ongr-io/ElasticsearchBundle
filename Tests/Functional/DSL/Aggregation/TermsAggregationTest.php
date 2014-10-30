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
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    ['key' => 'weak', 'doc_count' => 2],
                    ['key' => 'solid', 'doc_count' => 1],
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

        // Case #1 terms aggregation with limited size.
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setSize(1);

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    ['key' => 'weak', 'doc_count' => 2],
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

        // Case #2 terms aggregation with custom ordering.
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setOrder(TermsAggregation::MODE_TERM, TermsAggregation::DIRECTION_ASC);

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    ['key' => 'solid', 'doc_count' => 1],
                    ['key' => 'weak', 'doc_count' => 2],
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

        // Case #3 terms aggregation with minimum document count.
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setMinDocumentCount(2);

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    ['key' => 'weak', 'doc_count' => 2],
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

        // Case #4 terms aggregation with include.
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setInclude('sol.*');

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    ['key' => 'solid', 'doc_count' => 1],
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

        // Case #5 terms aggregation with exclude.
        $aggregation = new TermsAggregation('test_agg');
        $aggregation->setField('surface');
        $aggregation->setExclude('sol.*');

        $result = [
            'agg_test_agg' => [
                'buckets' => [
                    ['key' => 'weak', 'doc_count' => 2],
                ],
            ],
        ];

        $out[] = [$aggregation, $result];

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

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResult, $results['aggregations']);
    }
}
