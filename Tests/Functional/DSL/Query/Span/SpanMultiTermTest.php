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

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Query\FuzzyQuery;
use ONGR\ElasticsearchBundle\DSL\Query\PrefixQuery;
use ONGR\ElasticsearchBundle\DSL\Query\RangeQuery;
use ONGR\ElasticsearchBundle\DSL\Query\RegexpQuery;
use ONGR\ElasticsearchBundle\DSL\Query\Span\SpanMultiTermQuery;
use ONGR\ElasticsearchBundle\DSL\Query\WildcardQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SpanMultiTermTest extends AbstractElasticsearchTestCase
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
                        'price' => 5,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => 'foo bar foo',
                        'price' => 6,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'bar',
                        'description' => 'foo bar',
                        'price' => 9,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testSpanMultiTermQuery().
     *
     * @return array[]
     */
    public function getSpanMultiTermQueryData()
    {
        $out = [];

        // Case #0 with Prefix query.
        $query = new PrefixQuery('title', 'foo', ['boost' => 1.05]);
        $expected = [
            [
                'title' => 'foo',
                'description' => 'foo',
                'price' => 5,
            ],
        ];
        $out[] = [$query, $expected];

        // Case #1 with Wildcard query.
        $query = new WildcardQuery('title', 'fo*', ['boost' => 1.05]);
        $expected = [
            [
                'title' => 'foo',
                'description' => 'foo',
                'price' => 5,
            ],
        ];
        $out[] = [$query, $expected];

        // Case #2 with Range query.
        $query = new RangeQuery('title', [RangeQuery::GT => 'bar', RangeQuery::LT => 'foo bar']);
        $expected = [
            [
                'title' => 'foo',
                'description' => 'foo',
                'price' => 5,
            ],
        ];
        $out[] = [$query, $expected];

        // Case #3 with Regexp query.
        $query = new RegexpQuery('title', 'ba.*');
        $expected = [
            [
                'title' => 'bar',
                'description' => 'foo bar foo',
                'price' => 6,
            ],
            [
                'title' => 'bar',
                'description' => 'foo bar',
                'price' => 9,
            ],
        ];
        $out[] = [$query, $expected];

        // Case #4 with Fuzzy query.
        $query = new FuzzyQuery('title', 'foo');
        $expected = [
            [
                'title' => 'foo',
                'description' => 'foo',
                'price' => 5,
            ],
        ];

        $out[] = [$query, $expected];

        return $out;
    }

    /**
     * Tests span multi term query for expected search results.
     *
     * @param BuilderInterface $query
     * @param array            $expected
     *
     * @dataProvider getSpanMultiTermQueryData
     */
    public function testSpanMultiTermQuery($query, $expected)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $spanMultiTerm = new SpanMultiTermQuery($query);

        $search = $repo->createSearch()->addQuery($spanMultiTerm);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
