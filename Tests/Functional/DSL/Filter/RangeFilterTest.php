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

use Ongr\ElasticsearchBundle\DSL\Filter\RangeFilter;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class RangeFilterTest extends ElasticsearchTestCase
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
                        'price' => 10,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 70,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for the range filter.
     */
    public function testRangeFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $range = new RangeFilter('price', ['from' => 50, 'to' => 80]);
        $search = $repo->createSearch()->addFilter($range);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [
            [
                'title' => 'bar',
                'price' => 70,
            ],
        ];

        $this->assertEquals($expected, $results);
    }
}
