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

use ONGR\ElasticsearchBundle\DSL\Query\IndicesQuery;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class IndicesQueryTest extends ElasticsearchTestCase
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
     * Data provider for testAndFilter().
     *
     * @return array[]
     */
    public function getIndicesQueryData()
    {
        // Case #0.
        $out[] = [
            ['title', 'zoo'],
            ['title', 'bar'],
            [
                [
                    'title' => 'bar',
                    'price' => 100,
                ],
            ],
        ];

        // Case #1.
        $out[] = [
            ['title', 'zoo'],
            'none',
            [],
        ];

        return $out;
    }

    /**
     * Test for and filter.
     *
     * @param array $queryParams
     * @param array $noMatchQueryParams
     * @param array $expected
     *
     * @dataProvider getIndicesQueryData()
     */
    public function testAndFilter($queryParams, $noMatchQueryParams, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $query = new TermQuery($queryParams[0], $queryParams[1]);

        if (is_string($noMatchQueryParams)) {
            $noMatchQuery = $noMatchQueryParams;
        } else {
            $noMatchQuery = new TermQuery($noMatchQueryParams[0], $noMatchQueryParams[1]);
        }

        $indices = new IndicesQuery(['default'], $query, $noMatchQuery);

        $search = $repo->createSearch()->addQuery($indices);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
