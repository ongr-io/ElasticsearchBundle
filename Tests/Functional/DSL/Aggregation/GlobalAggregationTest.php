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

use ONGR\ElasticsearchBundle\DSL\Aggregation\GlobalAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\RangeAggregation;
use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Query\MatchQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class GlobalAggregationTest extends ElasticsearchTestCase
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
     * Data provider for testGlobalAggregation.
     *
     * @return array
     */
    public function getGlobalAggregationData()
    {
        $out = [];

        $results = [
            'agg_test_agg' => [
                'doc_count' => 3,
                'agg_test_agg2' => [
                    'buckets' => [
                        [
                            'key' => '*-40.0',
                            'to' => 40,
                            'to_as_string' => '40.0',
                            'doc_count' => 3,
                        ],
                    ],
                ],
            ],
        ];

        $aggregation = new GlobalAggregation('test_agg');

        $aggregation2 = new RangeAggregation('test_agg2');
        $aggregation2->setField('price');
        $aggregation2->addRange(null, 40);

        $aggregation->aggregations->addAggregation($aggregation2);

        // Case #0 global aggregation without query.
        $out[] = [$aggregation, null, $results, 3];

        // Case #1 global aggregation with query.
        $query = new MatchQuery('bar', 'title');

        $out[] = [$aggregation, $query, $results, 1];

        return $out;
    }

    /**
     * Test for global aggregation.
     *
     * @param GlobalAggregation     $aggregation
     * @param BuilderInterface|null $query
     * @param array                 $expectedResults
     * @param int                   $hitsCount
     *
     * @dataProvider getGlobalAggregationData
     */
    public function testGlobalAggregation($aggregation, $query, $expectedResults, $hitsCount)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()->addAggregation($aggregation);
        if ($query) {
            $search->addQuery($query);
        }

        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($hitsCount, $results['hits']['total']);

        $this->assertEquals($expectedResults, $results['aggregations']);
    }
}
