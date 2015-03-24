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
use Ongr\ElasticsearchBundle\DSL\Filter\QueryFilter;
use Ongr\ElasticsearchBundle\DSL\Query\TermQuery;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class QueryFilterTest extends ElasticsearchTestCase
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
     * Data provider for testQueryFilter().
     *
     * @return array[]
     */
    public function getQueryFilterData()
    {
        // Case #0 without cache.
        $out[] = [
            new TermQuery('title', 'bar'),
            [],
            [
                [
                    'title' => 'bar',
                    'price' => 100,
                ],
            ],
        ];

        // Case #1 with cache.
        $out[] = [
            new TermQuery('title', 'bar'),
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

        return $out;
    }

    /**
     * Test for query filter.
     *
     * @param BuilderInterface $query
     * @param array            $parameters
     * @param array            $expected
     *
     * @dataProvider getQueryFilterData()
     */
    public function testQueryFilter($query, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $not = new QueryFilter($query, $parameters);
        $search = $repo->createSearch()->addFilter($not);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
