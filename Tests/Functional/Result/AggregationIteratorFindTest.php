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

use ONGR\ElasticsearchBundle\DSL\Aggregation\RangeAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
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
                        'description' => 'solid',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => 'weak',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'pizza',
                        'description' => 'weak',
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
                    'buckets' => [
                        [
                            'key' => '*-20.0',
                            'to' => 20.0,
                            'to_as_string' => '20.0',
                            'doc_count' => 1,
                        ],
                        [
                            'key' => '20.0-*',
                            'from' => 20.0,
                            'from_as_string' => '20.0',
                            'doc_count' => 1,
                        ],
                    ],
                ],
            ],
            [
                'key' => 'solid',
                'doc_count' => 1,
                'agg_test_agg_2' => [
                    'buckets' => [
                        [
                            'key' => '*-20.0',
                            'to' => 20.0,
                            'to_as_string' => '20.0',
                            'doc_count' => 1,
                        ],
                        [
                            'key' => '20.0-*',
                            'from' => 20.0,
                            'from_as_string' => '20.0',
                            'doc_count' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $out[] = [
            'test_agg',
            new AggregationIterator($rawData),
        ];

        $rawData = [
            [
                'key' => '*-20.0',
                'to' => 20.0,
                'to_as_string' => '20.0',
                'doc_count' => 1,
            ],
            [
                'key' => '20.0-*',
                'from' => 20.0,
                'from_as_string' => '20.0',
                'doc_count' => 1,
            ],
        ];

        $out[] = [
            'test_agg.0.test_agg_2',
            new AggregationIterator($rawData),
        ];

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
        $repository = $this
            ->getManager()
            ->getRepository('AcmeTestBundle:Product');
        $search = $repository
            ->createSearch()
            ->addAggregation($this->buildAggregation());
        $results = $repository->execute($search);
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
        $aggregation->setField('description');
        $aggregation2 = new RangeAggregation('test_agg_2');
        $aggregation2->setField('price');
        $aggregation2->addRange(null, 20);
        $aggregation2->addRange(20, null);
        $aggregation->addAggregation($aggregation2);

        return $aggregation;
    }
}
