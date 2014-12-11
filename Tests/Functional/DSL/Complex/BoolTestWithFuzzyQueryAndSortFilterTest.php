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

use ONGR\ElasticsearchBundle\DSL\Query\FuzzyQuery;
use ONGR\ElasticsearchBundle\DSL\Sort\Sort;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class BoolTestWithFuzzyQueryAndSortFilterTest extends ElasticsearchTestCase
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
     * Data provider for testBoolWithFuzzyQueryAndSortFilter().
     *
     * @return array
     */
    public function getTestBoolWithFuzzyQueryAndSortFilterData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 should return the product with price in range [245..275], sort by price.
        $out[] = [
            'price',
            260,
            [
                'fuzziness' => 15,
            ],
            'price',
            [
                $testProducts[1],
                $testProducts[2],
                $testProducts[3],
            ],
        ];

        // Case #1 should return the product with price in range [0..1000], sort by price.
        $out[] = [
            'price',
            500,
            [
                'fuzziness' => 500,
            ],
            'price',
            $testProducts,
        ];

        // Case #2 empty return.
        $out[] = [
            'price',
            1,
            [
                'fuzziness' => 1,
            ],
            'price',
            [],
        ];

        return $out;
    }

    /**
     * Test Fuzzy query for expected search results.
     *
     * @param string $field
     * @param string $value
     * @param array  $parameters
     * @param string $sort_field
     * @param array  $expected
     *
     * @dataProvider getTestBoolWithFuzzyQueryAndSortFilterData
     */
    public function testBoolWithFuzzyQueryAndSortFilter($field, $value, $parameters, $sort_field, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $FuzzyQuery = new FuzzyQuery($field, $value, $parameters);
        $SortFilter = new Sort($sort_field);
        $search = $repo->createSearch()->addQuery($FuzzyQuery);
        $search->addSort($SortFilter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
