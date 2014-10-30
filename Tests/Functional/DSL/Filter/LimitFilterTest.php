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

use ONGR\ElasticsearchBundle\DSL\Filter\LimitFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class LimitFilterTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        $i = 0;
        do {
            $products[] = [
                '_id' => $i,
                'title' => "bar $i",
                'price' => 7 * $i,
            ];

            $i++;
        } while ($i < 20);

        return [
            'default' => [
                'product' => $products,
            ],
        ];
    }

    /**
     * Test for limit filter.
     */
    public function testLimitFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $limit = new LimitFilter(1);
        $search = $repo->createSearch()->addFilter($limit);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals(5, count($results));
    }
}
