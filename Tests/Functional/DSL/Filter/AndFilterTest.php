<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Filter;

use ONGR\ElasticsearchDSL\Filter\AndFilter;
use ONGR\ElasticsearchDSL\Filter\MissingFilter;
use ONGR\ElasticsearchDSL\Filter\PrefixFilter;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class AndFilterTest extends AbstractElasticsearchTestCase
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
                        'description' => 'super foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'barbara',
                        'description' => 'super bar',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'foot',
                        'price' => 300,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testAndFilter().
     *
     * @return array[]
     */
    public function getAndFilterData()
    {
        // Case #0 with cache.
        $out[] = [
            'title',
            'ba',
            'price',
            [
                '_cache' => true,
            ],
            [
                [
                    'title' => 'barbara',
                    'description' => 'super bar',
                ],
            ],
        ];

        // Case #1 without cache.
        $out[] = [
            'title',
            'fo',
            'description',
            [
                '_cache' => false,
            ],
            [
                [
                    'title' => 'foot',
                    'price' => 300,
                ],
            ],
        ];

        return $out;
    }

    /**
     * Tests "and" filter.
     *
     * @param string $prefixField  Prefix filter field name.
     * @param string $prefixValue  Prefix filter field value.
     * @param string $missingField Field for missing filter.
     * @param array  $parameters   Additional parameters for AndFilter.
     * @param array  $expected     Expected result.
     *
     * @dataProvider getAndFilterData()
     */
    public function testAndFilter($prefixField, $prefixValue, $missingField, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $missing = new MissingFilter($missingField);
        $prefix = new PrefixFilter($prefixField, $prefixValue);
        $filters = [$missing, $prefix];
        $and = new AndFilter($filters, $parameters);

        $search = $repo->createSearch()->addFilter($and);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
