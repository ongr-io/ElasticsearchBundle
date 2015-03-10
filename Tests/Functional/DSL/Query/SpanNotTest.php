<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Query\SpanNearQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanNotQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanTermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SpanNotTest extends AbstractElasticsearchTestCase
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
                        'price' => 10,
                        'description' => 'Lorem',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'foo bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test Span not query with different span queries, we expecting to get 0 results.
     */
    public function testSpanNotTest()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanNot = new SpanNotQuery(
            new SpanTermQuery('description', 'Lorem ipsum'),
            new SpanNearQuery(
                [
                    new SpanTermQuery('description', 'consectetur'),
                    new SpanTermQuery('description', 'foo'),
                ],
                [
                    'slop' => 1,
                ]
            )
        );
        $search = $repo->createSearch()->addQuery($spanNot);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(0, count($results));
    }

    /**
     * Test Span not query.
     */
    public function testSpanNotTestSimpleSpanQueries()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanNot = new SpanNotQuery(
            new SpanTermQuery('description', 'foo'),
            new SpanNearQuery(
                [
                    new SpanTermQuery('description', 'consectetur'),
                ],
                [
                    'slop' => 1,
                ]
            )
        );
        $search = $repo->createSearch()->addQuery($spanNot);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(1, count($results));
    }
}
