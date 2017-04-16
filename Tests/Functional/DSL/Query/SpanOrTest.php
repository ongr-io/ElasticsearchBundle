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

use ONGR\ElasticsearchBundle\DSL\Query\SpanFirstQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanNearQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanOrQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanTermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SpanOrTest extends AbstractElasticsearchTestCase
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
                ],
            ],
        ];
    }

    /**
     * Test Span or query with different span queries.
     */
    public function testSpanOrQuery()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanOr = new SpanOrQuery(
            [
                new SpanFirstQuery(new SpanTermQuery('description', 'ipsum'), ['end' => 2]),
                new SpanNearQuery(
                    [
                        new SpanTermQuery('description', 'ipsum'),
                        new SpanTermQuery('description', 'sit'),
                    ],
                    [
                        'in_order' => true,
                        'slop' => 1,
                    ]
                ),
            ]
        );

        $search = $repo->createSearch()->addQuery($spanOr);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(2, count($results));
    }
}
