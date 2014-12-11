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

use ONGR\ElasticsearchBundle\DSL\Filter\IndicesFilter;
use ONGR\ElasticsearchBundle\DSL\Filter\PrefixFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class IndicesFilterTest extends ElasticsearchTestCase
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
                        'title' => 'zoo',
                        'description' => 'super zoo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'apple',
                        'description' => 'red apple',
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
     * Data provider for testIndicesFilter().
     *
     * @return array[]
     */
    public function getIndicesFilterData()
    {
        // Case #0.
        $out[] = [
            ['title', 'fo'],
            ['title', 'ba'],
            [
                [
                    'title' => 'bar',
                    'price' => 100,
                ],
            ],
        ];

        // Case #1.
        $out[] = [
            ['title', 'ba'],
            'none',
            [],
        ];

        return $out;
    }

    /**
     * Test for indices filter.
     *
     * @param array $filterParams
     * @param array $noMatchFilterParams
     * @param array $expected
     *
     * @dataProvider getIndicesFilterData()
     */
    public function testIndicesFilter($filterParams, $noMatchFilterParams, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $filter = new PrefixFilter($filterParams[0], $filterParams[1]);

        if (is_string($noMatchFilterParams)) {
            $noMatchFilter = $noMatchFilterParams;
        } else {
            $noMatchFilter = new PrefixFilter($noMatchFilterParams[0], $noMatchFilterParams[1]);
        }

        $indices = new IndicesFilter(['default'], $filter, $noMatchFilter);
        $search = $repo->createSearch()->addFilter($indices);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
