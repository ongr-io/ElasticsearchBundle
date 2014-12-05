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

use ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter;
use ONGR\ElasticsearchBundle\DSL\Filter\NotFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class NotFilterTest extends ElasticsearchTestCase
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
                ],
            ],
        ];
    }

    /**
     * Data provider for testNotFilter().
     *
     * @return array[]
     */
    public function getNotFilterData()
    {
        // Case #0 with cache.
        $out[] = [
            'price',
            [
                '_cache' => true,
            ],
            [
                [
                    'title' => 'bar',
                    'price' => 100,
                ],
            ],
        ];

        // Case #1 without cache.
        $out[] = [
            'description',
            [],
            [
                [
                    'title' => 'foo',
                    'description' => 'super foo',
                ],
            ],
        ];

        return $out;
    }

    /**
     * Test for not filter.
     *
     * @param string $missingField Field name.
     * @param array  $parameters   Additional parameters.
     * @param array  $expected     Expected result.
     *
     * @dataProvider getNotFilterData()
     */
    public function testNotFilter($missingField, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $missing = new MissingFilter($missingField);
        $not = new NotFilter($missing, $parameters);
        $search = $repo->createSearch()->addFilter($not);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
