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

use ONGR\ElasticsearchBundle\DSL\Query\DisMaxQuery;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Dis Max Query functional tests.
 */
class DisMaxQueryTest extends AbstractElasticsearchTestCase
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
     * Tests dis max query for expected search result.
     */
    public function testDixMaxQuery()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $disMaxQuery = new DisMaxQuery(
            [
                new TermQuery('title', 'foo'),
                new TermQuery('title', 'bar'),
            ],
            [
                'tie_breaker' => 0.7,
            ]
        );
        $search = $repo->createSearch()->addQuery($disMaxQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expected = [
            ['title' => 'foo', 'description' => 'foo'],
            ['title' => 'bar', 'description' => 'foo baz'],
        ];

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests if exception is thrown.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testDixMaxQueryIfExceptionIsThrown()
    {
        new DisMaxQuery([new TermQuery('title', 'foo'), new \stdClass()]);
    }
}
