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

use ONGR\ElasticsearchBundle\DSL\Query\MoreLikeThisQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class MoreLikeThisTest extends ElasticsearchTestCase
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
     * Data provider for testMoreLikeThisQuery().
     *
     * @return array
     */
    public function getTestMoreLikeThisQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 all product titles in query - should match all products.
        $out[] = [
            'foo bar baz',
            [
                'min_term_freq' => 1,
                'max_query_terms' => 12,
                'min_doc_freq' => 1,
            ],
            $testProducts,
        ];

        // Case #1 all product titles in query but searching only in description field - should match no products.
        $out[] = [
            'foo bar baz',
            [
                'fields' => [
                    'description',
                ],
                'min_term_freq' => 1,
                'max_query_terms' => 12,
                'min_doc_freq' => 1,
            ],
            [],
        ];

        // Case #2 query that matches last two product descriptions only.
        $out[] = [
            'dolor sit amet',
            [
                'fields' => [
                    'description',
                ],
                'min_term_freq' => 1,
                'max_query_terms' => 12,
                'min_doc_freq' => 1,
            ],
            [
                $testProducts[1],
                $testProducts[2],
            ],
        ];

        return $out;
    }

    /**
     * Test MoreLikeThis query for expected search results.
     *
     * @param string $query
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestMoreLikeThisQueryData
     */
    public function testMoreLikeThisQuery($query, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $moreLikeThisQuery = new MoreLikeThisQuery($query, $parameters);

        $search = $repo->createSearch()->addQuery($moreLikeThisQuery);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
