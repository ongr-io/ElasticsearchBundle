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

use ONGR\ElasticsearchBundle\DSL\Query\BoostingQuery;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Query\TermsQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * BoostingQuery functional tests.
 */
class BoostingQueryTest extends AbstractElasticsearchTestCase
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
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => 'foo baz',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'description' => 'foo bar baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * BoostingQuery test with positive TermQuery.
     */
    public function testBoostingQueryWithPositiveTermQuery()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $boostingQuery = new BoostingQuery(new TermQuery('title', 'foo'), new TermQuery('title', 'bar'), 0.2);
        $search = $repo->createSearch()->addQuery($boostingQuery);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertEquals(0.30685282, $results['hits']['max_score']);
    }

    /**
     * BoostingQuery test with negative TermsQuery.
     */
    public function testBoostingQueryWithPositiveTermsQuery()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $boostingQuery = new BoostingQuery(new TermQuery('title', 'foo'), new TermsQuery('title', ['foo', 'baz']), 0.2);
        $search = $repo->createSearch()->addQuery($boostingQuery);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertEquals(0.061370563, $results['hits']['max_score']);
    }
}
