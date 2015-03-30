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

use Ongr\ElasticsearchBundle\DSL\Filter\MatchAllFilter;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class MatchAllFilterTest extends ElasticsearchTestCase
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
            ],
        ];
    }

    /**
     * Test for match_all filter.
     */
    public function testMatchAllFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $matchAll = new MatchAllFilter();
        $search = $repo->createSearch()->addFilter($matchAll);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [
            [
                'title' => 'foo',
            ],
        ];

        $this->assertEquals($expected, $results);
    }
}
