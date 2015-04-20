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

use ONGR\ElasticsearchBundle\DSL\Query\RangeQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Range query query functional test.
 */
class RangeTest extends ElasticsearchTestCase
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
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 50,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testRangeQuery().
     *
     * @return array
     */
    public function getTestRangeQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 10 < price < 100 - should return only second product.
        $out[] = [[RangeQuery::GT => 10, RangeQuery::LT => 100], [$testProducts[1]]];

        // Case #1 price <= 100 OR price < 100 - should return all products.
        $out[] = [[RangeQuery::LT => 100, RangeQuery::LTE => 100], array_reverse($testProducts)];

        // Case #2 price >= 10 OR price > 10 - should return all products.
        $out[] = [[RangeQuery::GT => 10, RangeQuery::GTE => 10], array_reverse($testProducts)];

        return $out;
    }

    /**
     * Test range query for expected search result.
     *
     * @param array $parameters Additional parameters.
     * @param array $expected   Expected result.
     *
     * @dataProvider getTestRangeQueryData
     */
    public function testRangeQuery($parameters, $expected)
    {
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repository
            ->createSearch()
            ->addQuery(new RangeQuery('price', $parameters));

        $results = $repository->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
