<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DSL\Query\Span;

use Ongr\ElasticsearchBundle\DSL\Query\Span\SpanFirstQuery;
use Ongr\ElasticsearchBundle\DSL\Query\Span\SpanNearQuery;
use Ongr\ElasticsearchBundle\DSL\Query\Span\SpanOrQuery;
use Ongr\ElasticsearchBundle\DSL\Query\Span\SpanTermQuery;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

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
        $spanNear = new SpanNearQuery(['in_order' => true]);
        $spanNear->setSlop(1);
        $spanNear
            ->addQuery(new SpanTermQuery('description', 'ipsum'))
            ->addQuery(new SpanTermQuery('description', 'sit'));

        $spanOr = new SpanOrQuery();
        $spanOr->addQuery(new SpanFirstQuery(new SpanTermQuery('description', 'ipsum'), 2))
            ->addQuery($spanNear);

        $search = $repo->createSearch()->addQuery($spanOr);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(2, count($results));
    }
}
