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

use ONGR\ElasticsearchBundle\DSL\Filter\IdsFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class IdsFilterTest extends ElasticsearchTestCase
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
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'wheel',
                        'price' => 333,
                    ],
                ],
                'category' => [
                    [
                        '_id' => 1,
                        'title' => 'glass',
                        'price' => 200,
                    ],
                ],
                'tag' => [
                    [
                        '_id' => 3,
                        'title' => 'door',
                        'price' => 500,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testIdsFilter().
     *
     * @return array[]
     */
    public function getIdsFilterData()
    {
        // Case #0 with type.
        $out[] = [
            [
                '1',
            ],
            [
                'type' => 'product',
            ],
            [
                [
                    'title' => 'foo',
                ],
            ],
        ];

        // Case #1 without type.
        $out[] = [
            [
                '3',
            ],
            [],
            [
                [
                    'title' => 'wheel',
                    'price' => 333,
                ],
            ],
        ];

        return $out;
    }

    /**
     * Test for ids filter.
     *
     * @param array $values     Ids values.
     * @param array $parameters Additional parameters.
     * @param array $expected   Expected result.
     *
     * @dataProvider getIdsFilterData()
     */
    public function testIdsFilter($values, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $ids = new IdsFilter($values, $parameters);
        $search = $repo->createSearch()->addFilter($ids);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
