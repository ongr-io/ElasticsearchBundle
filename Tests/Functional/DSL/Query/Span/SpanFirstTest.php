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
use Ongr\ElasticsearchBundle\DSL\Query\Span\SpanTermQuery;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SpanFirstTest extends AbstractElasticsearchTestCase
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
                        'description' => 'Lorem ipsum',
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
     * Test when parameter end is equals to 1.
     */
    public function testSpanFirstQuery()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanFirst = new SpanFirstQuery(new SpanTermQuery('description', 'dolor'), 1);
        $search = $repo->createSearch()->addQuery($spanFirst);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(0, count($results));
    }

    /**
     * Test when parameter end is equals to 3.
     */
    public function testSpanFirstQueryExpectingToGetMoreResults()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanFirst = new SpanFirstQuery(new SpanTermQuery('description', 'dolor'), 3);
        $search = $repo->createSearch()->addQuery($spanFirst);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expectedResult = [
            [
                'title' => 'bar',
                'price' => 100,
                'description' => 'Lorem ipsum dolor sit amet...',
            ],
            [
                'title' => 'baz',
                'price' => 1000,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
            ],
        ];
        $this->assertEquals($expectedResult, $results);
    }
}
