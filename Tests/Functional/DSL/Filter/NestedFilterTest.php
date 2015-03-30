<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DSL\Filter;

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;
use Ongr\ElasticsearchBundle\DSL\Filter\MatchAllFilter;
use Ongr\ElasticsearchBundle\DSL\Filter\NestedFilter;
use Ongr\ElasticsearchBundle\DSL\Filter\RangeFilter;
use Ongr\ElasticsearchBundle\DSL\Filter\TermsFilter;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Nested Filter functional test.
 */
class NestedFilterTest extends AbstractElasticsearchTestCase
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
     * Data provider for testNestedFilter.
     *
     * @return array
     */
    public function getTestNestedFilterData()
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
        $query = new NestedFilter('sub_product', new RangeFilter('sub_product.price', ['from' => 100]));

        $out[] = [
            $query,
            [
                $testData['default']['product'][2],
                $testData['default']['product'][1],
            ],
            $mapping,
        ];

        // Case #1: Test MatchAll with no data.
        $query = new NestedFilter('sub_product', new MatchAllFilter());

        $out[] = [
            $query,
            [
                $testData['default']['product'][1],
                $testData['default']['product'][2],
                $testData['default']['product'][0],
            ],
            $mapping,
        ];

        // Case #2: Test terms filter.
        $query = new NestedFilter(
            'sub_product',
            new TermsFilter('sub_product.title', ['foo']),
            ['_cache' => true, '_name' => 'named']
        );

        $out[] = [$query, [$testData['default']['product'][0]], $mapping];

        return $out;
    }

    /**
     * Test NestedFilter for expected search results.
     *
     * @param BuilderInterface $query
     * @param array            $expected
     * @param array            $mapping
     *
     * @dataProvider getTestNestedFilterData
     */
    public function testNestedFilter($query, $expected, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch()->addFilter($query, 'must');
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);
        $this->assertEquals($expected, $results);

        $search = $repo->createSearch()->addFilter($query);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        $this->assertEquals($expected, $results);
    }
}
