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

use ONGR\ElasticsearchBundle\DSL\Filter\OrFilter;
use ONGR\ElasticsearchBundle\DSL\Filter\TermFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class OrFilterTest extends ElasticsearchTestCase
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
                        'description' => 'foo desc',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => 'bar desc',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'description' => 'baz bar',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'cuz',
                        'description' => 'cuz baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testOrFilter().
     *
     * @return array[]
     */
    public function getOrFilterData()
    {
        // Case #0 with cache.
        $out[] = [
            'title',
            'baz',
            'description',
            'baz bar',
            [
                '_cache' => true,
            ],
            [
                [
                    'title' => 'baz',
                    'description' => 'baz bar',
                ],
            ],
        ];

        // Case #1 without cache.
        $out[] = [
            'title',
            'cuz',
            'description',
            'cuz baz',
            [],
            [
                [
                    'title' => 'cuz',
                    'description' => 'cuz baz',
                ],
            ],
        ];

        return $out;
    }

    /**
     * Test for the or filter.
     *
     * @param string $termFieldOne First term name.
     * @param string $termValueOne First term value.
     * @param string $termFieldTwo Second term name.
     * @param string $termValueTwo Second term value.
     * @param array  $parameters   Additional parameters.
     * @param array  $expected     Expected result.
     *
     * @dataProvider getOrFilterData()
     */
    public function testOrFilter($termFieldOne, $termValueOne, $termFieldTwo, $termValueTwo, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $filters = [
            new TermFilter($termFieldOne, $termValueOne),
            new TermFilter($termFieldTwo, $termValueTwo),
        ];

        $or = new OrFilter($filters, $parameters);

        $search = $repo->createSearch()->addFilter($or);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
