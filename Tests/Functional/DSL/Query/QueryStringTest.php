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

use ONGR\ElasticsearchBundle\DSL\Query\QueryStringQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * QueryString query functional test
 */
class QueryStringTest extends ElasticsearchTestCase
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
                        'price' => 100,
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 10,
                        'description' => 'foo baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testQueryStringQuery().
     *
     * @return array
     */
    public function getTestQueryStringQueryData()
    {
        $out = [];

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 OR statement returns both products.
        $out[] = ['foo OR baz', [], $testProducts];

        // Case #1 AND statement should return second product only.
        $out[] = ['foo AND baz', [], [$testProducts[1]]];

        // Case #2 AND statement search only in description field should return empty array.
        $out[] = [
            'foo AND bar',
            [
                'fields' => [
                    'description',
                ],
                'boost' => 2,
            ],
            [],
        ];

        return $out;
    }

    /**
     * Test query string for expected search result.
     *
     * @param string $query
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestQueryStringQueryData
     */
    public function testQueryStringQuery($query, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $queryStringQuery = new QueryStringQuery($query, $parameters);

        $search = $repo->createSearch()->addQuery($queryStringQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
