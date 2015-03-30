<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DSL\Sort;

use Ongr\ElasticsearchBundle\DSL\Filter\IdsFilter;
use Ongr\ElasticsearchBundle\DSL\Sort\Sort;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

class SortTest extends ElasticsearchTestCase
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
                        'description' => 'A',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'B',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'C',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'zaz',
                        'price' => 1000,
                        'description' => 'D',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider testSimpleSort.
     *
     * @return array
     */
    public function simpleSortData()
    {
        $out = [];

        // Case #0 simple sort.
        $sorts[] = ['field' => 'description', 'order' => Sort::ORDER_DESC, 'nested' => null, 'mode' => null];
        $expectedIds = [4, 3, 2, 1];
        $out[] = [$sorts, $expectedIds];

        // Case #1 ascending sort.
        $sorts = [];
        $sorts[] = ['field' => 'description', 'order' => Sort::ORDER_ASC, 'nested' => null, 'mode' => null];
        $expectedIds = [1, 2, 3, 4];
        $out[] = [$sorts, $expectedIds];

        // Case #2 sorting by multiple fields.
        $sorts = [];
        $sorts[] = ['field' => 'price', 'order' => Sort::ORDER_ASC, 'nested' => null, 'mode' => null];
        $sorts[] = ['field' => 'title', 'order' => Sort::ORDER_DESC, 'nested' => null, 'mode' => null];
        $expectedIds = [1, 2, 4, 3];
        $out[] = [$sorts, $expectedIds];

        // Case #3 sorting using nested filter.
        $sorts = [];
        $sorts[] = ['field' => 'price', 'order' => Sort::ORDER_DESC, 'nested' => new IdsFilter([2, 1]), 'mode' => null];
        $sorts[] = ['field' => 'title', 'order' => Sort::ORDER_DESC, 'nested' => new IdsFilter([3, 4]), 'mode' => null];
        $expectedIds = [4, 3, 2, 1];
        $out[] = [$sorts, $expectedIds];

        // Case #4 sorting with mode.
        $sorts = [];
        $sorts[] = ['field' => 'description', 'order' => Sort::ORDER_DESC, 'nested' => null, 'mode' => Sort::MODE_AVG];
        $expectedIds = [4, 3, 2, 1];
        $out[] = [$sorts, $expectedIds];

        return $out;
    }

    /**
     * Check if simple sorting works as expected.
     *
     * @param array $sorts
     * @param array $expectedIds
     *
     * @dataProvider simpleSortData()
     */
    public function testSimpleSort($sorts, $expectedIds)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch();
        foreach ($sorts as $sort) {
            $search->addSort(new Sort($sort['field'], $sort['order'], $sort['nested'], $sort['mode']));
        }
        $results = $repo->execute($search, Repository::RESULTS_RAW_ITERATOR);
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result['_id'];
        }

        $this->assertEquals($expectedIds, $ids);
    }
}
