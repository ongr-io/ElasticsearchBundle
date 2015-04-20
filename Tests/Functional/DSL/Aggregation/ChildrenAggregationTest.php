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

use ONGR\ElasticsearchBundle\DSL\Aggregation\ChildrenAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class ChildrenAggregationTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.4.0', '<'],
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
                        'title' => 'product title',
                    ],
                ],
                'comment' => [
                    [
                        '_parent' => 1,
                        'sub_title' => 'foo',
                    ],
                    [
                        '_parent' => 1,
                        'sub_title' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testChildrenAggregation.
     *
     * @return array
     */
    public function getChildrenAggregationData()
    {
        $out = [];

        $mapping = [
            'product' => [
                'properties' => [
                    'title' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'comment' => [
                '_parent' => [
                    'type' => 'product',
                ],
                '_routing' => [
                    'required' => true,
                ],
                'properties' => [
                    'sub_title' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];

        // Case #0 simple terms aggregation.
        $aggregation = new TermsAggregation('test_terms_agg');
        $aggregation->setField('comment.sub_title');

        $result = [
            'doc_count' => 2,
            'agg_test_terms_agg' => [
                'buckets' => [
                    [
                        'key' => 'bar',
                        'doc_count' => 1,
                    ],
                    [
                        'key' => 'foo',
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

        return $out;
    }

    /**
     * Test for children aggregation.
     *
     * @param AbstractAggregation $aggregation
     * @param array               $expectedResult
     * @param array               $mapping
     *
     * @dataProvider getChildrenAggregationData
     */
    public function testChildrenAggregation($aggregation, $expectedResult, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Product');

        $childrenAggregation = new ChildrenAggregation('test_children_agg');
        $childrenAggregation->setChildren('comment');

        $childrenAggregation->addAggregation($aggregation);

        $search = $repo->createSearch()->addAggregation($childrenAggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertArraySubset($expectedResult, $results['aggregations']['agg_test_children_agg']);
    }
}
