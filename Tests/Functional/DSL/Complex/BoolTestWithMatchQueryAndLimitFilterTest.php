<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Complex;

use ONGR\ElasticsearchBundle\DSL\Filter\LimitFilter;
use ONGR\ElasticsearchBundle\DSL\Query\MatchQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class BoolTestWithMatchQueryAndLimitFilterTest extends ElasticsearchTestCase
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
                        'title' => 'baz',
                        'price' => 244,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 256,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 260,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'foo',
                        'price' => 275,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 5,
                        'title' => 'baz',
                        'price' => 276,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 6,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'Lorem ipsum',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testBoolWithMatchQueryAndLimitFilter().
     *
     * @return array
     */
    public function getTestBoolTestWithMatchQueryAndLimitFilterData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Should return the product with price in range [245..275], sort by price.
        $out[] = [
            'Lorem',
            1,
            [
                $testProducts[0],
                $testProducts[2],
                $testProducts[4],
                $testProducts[3],
                $testProducts[1],
            ],
        ];

        return $out;
    }

    /**
     * Test Fuzzy query for expected search results.
     *
     * @param string $query
     * @param int    $limit
     * @param array  $expected
     *
     * @dataProvider getTestBoolTestWithMatchQueryAndLimitFilterData
     */
    public function testBoolWithMatchQueryAndLimitFilter($query, $limit, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $matchQuery = new MatchQuery($query, 'description');
        $limitFilter = new LimitFilter($limit);
        $search = $repo->createSearch()->addQuery($matchQuery);
        $search->addFilter($limitFilter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
