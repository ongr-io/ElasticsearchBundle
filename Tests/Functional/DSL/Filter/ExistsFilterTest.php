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

use Ongr\ElasticsearchBundle\DSL\Filter\ExistsFilter;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class ExistsFilterTest extends ElasticsearchTestCase
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
     * Test for exists filter.
     */
    public function testExistsFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $exists = new ExistsFilter('price');
        $search = $repo->createSearch()->addFilter($exists);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [
            [
                'title' => 'bar',
                'price' => 100,
            ],
        ];

        $this->assertEquals($expected, $results);
    }
}
