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

use Ongr\ElasticsearchBundle\DSL\Filter\PrefixFilter;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class PrefixFilterTest extends ElasticsearchTestCase
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
                ],
            ],
        ];
    }

    /**
     * Test for prefix filter.
     */
    public function testPrefixFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $prefix = new PrefixFilter('title', 'f', ['_cache' => true]);
        $search = $repo->createSearch()->addFilter($prefix);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [
            [
                'title' => 'foo',
            ],
        ];

        $this->assertEquals($expected, $results);
    }
}
