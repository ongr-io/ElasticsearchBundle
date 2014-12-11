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

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Query\FuzzyQuery;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\DSL\Query\NestedQuery;
use ONGR\ElasticsearchBundle\DSL\Query\RangeQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class NestedQueryTest extends ElasticsearchTestCase
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
                        'sub_product' => [
                            '_id' => 1,
                            'title' => 'foo',
                            'price' => 10,
                        ],
                    ],
                    [
                        'sub_product' => [
                            '_id' => 2,
                            'title' => 'bar',
                            'price' => 100,
                        ],
                    ],
                    [
                        'sub_product' => [
                            '_id' => 3,
                            'title' => 'baz',
                            'price' => 1000,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testNestedQuery().
     *
     * @return array
     */
    public function getTestNestedQueryData()
    {
        $out = [];
        $testData = $this->getDataArray();

        $mapping = [
            'product' => [
                'properties' => [
                    'sub_product' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ],
                            'title' => [
                                'type' => 'string',
                            ],
                            'price' => [
                                'type' => 'float',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Case #0: Test range.
        $query = new NestedQuery('sub_product', new RangeQuery('sub_product.price', ['from' => 100]));

        $out[] = [
            $query,
            [
                $testData['default']['product'][2],
                $testData['default']['product'][1],
            ],
            $mapping
        ];

        // Case #1: Test MatchAll with no data.
        $query = new NestedQuery('sub_product', new MatchAllQuery());

        $out[] = [
            $query,
            [
                $testData['default']['product'][1],
                $testData['default']['product'][2],
                $testData['default']['product'][0],
            ],
            $mapping
        ];

        // Case #2: Test fuzzy.
        $query = new NestedQuery('sub_product', new FuzzyQuery('sub_product.price', 10, ['fuzziness' => 10]));

        $out[] = [$query, [$testData['default']['product'][0]], $mapping];

        return $out;
    }

    /**
     * Test Ids query for expected search results.
     *
     * @param BuilderInterface  $query
     * @param array             $expected
     * @param array             $mapping
     *
     * @dataProvider getTestNestedQueryData
     */
    public function testNestedQuery($query, $expected, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch()->addQuery($query, 'must');
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);
        $this->assertEquals($expected, $results);

        $search = $repo->createSearch()->addQuery($query);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        $this->assertEquals($expected, $results);
    }
}
