<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Query\Span;

use ONGR\ElasticsearchBundle\DSL\Query\Span\SpanNearQuery;
use ONGR\ElasticsearchBundle\DSL\Query\Span\SpanTermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SpanNearTest extends AbstractElasticsearchTestCase
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
                        'description' => [
                            'ONE BAR BAR BAR TWO BAR BAR BAR THREE',
                            'FOUR BAR BAR BAR FIVE BAR BAR BAR SIX',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests span near query for expected search result.
     */
    public function testSpanNearQueryWhenSlopIsLow()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanNear = new SpanNearQuery(['in_order' => true]);
        $spanNear->setSlop(12);
        $spanNear
            ->addQuery(new SpanTermQuery('description', 'one'))
            ->addQuery(new SpanTermQuery('description', 'two'))
            ->addQuery(new SpanTermQuery('description', 'six'));

        $search = $repo->createSearch()->addQuery($spanNear);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(0, count($results));
    }

    /**
     * Tests span near query for expected search result.
     */
    public function testSpanNearQueryWhenSlopIsLarge()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanNear = new SpanNearQuery(['in_order' => true]);
        $spanNear->setSlop(40);
        $spanNear
            ->addQuery(new SpanTermQuery('description', 'one'))
            ->addQuery(new SpanTermQuery('description', 'two'))
            ->addQuery(new SpanTermQuery('description', 'six'));

        $search = $repo->createSearch()->addQuery($spanNear);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(1, count($results));
    }
}
