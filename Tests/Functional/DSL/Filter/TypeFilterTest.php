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

use Ongr\ElasticsearchBundle\DSL\Filter\TypeFilter;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class TypeFilterTest extends ElasticsearchTestCase
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
                ],
                'category' => [
                    [
                        '_id' => 1,
                        'title' => 'foo_cat',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar_cat',
                        'sorting' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for type filter.
     */
    public function testTypeFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $type = new TypeFilter('product');
        $search = $repo->createSearch()->addFilter($type);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [
            [
                'title' => 'foo',
            ],
        ];

        $this->assertEquals($expected, $results);
    }
}
