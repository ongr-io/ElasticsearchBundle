<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Sort;

use ONGR\ElasticsearchBundle\DSL\Sort\ScriptSort;
use ONGR\ElasticsearchBundle\DSL\Sort\Sort;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class ScriptSortTest extends ElasticsearchTestCase
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
                        'location' => [40, 10],
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 20,
                        'location' => [20, 30],
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 30,
                        'location' => [0, 20],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testScriptSort.
     *
     * @return array
     */
    public function scriptSortData()
    {
        $out = [];

        // Case #0 script sort without any params.
        $sorts[] = [
            'script' => "doc['price'].value + doc['location'].lon",
            'order' => Sort::ORDER_DESC,
            'params' => null,
            'type' => 'number',
        ];
        $expectedIds = [1, 2, 3];
        $out[] = [$sorts, $expectedIds];

        // Case #1 script sort with params.
        $sorts[] = [
            'script' => "test_param/(doc['location'].lon+1)",
            'order' => Sort::ORDER_ASC,
            'params' => ['test_param' => 1.2],
            'type' => 'number',
        ];
        $expectedIds = [1, 2, 3];
        $out[] = [$sorts, $expectedIds];

        return $out;
    }

    /**
     * Check if script sorting works as expected.
     *
     * @param array $sorts
     * @param array $expectedIds
     *
     * @dataProvider scriptSortData()
     */
    public function testScriptSort($sorts, $expectedIds)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch();
        foreach ($sorts as $sort) {
            $search->addSort(new ScriptSort($sort['script'], $sort['type'], $sort['params'], $sort['order']));
        }
        $results = $repo->execute($search, Repository::RESULTS_RAW_ITERATOR);
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result['_id'];
        }

        $this->assertEquals($expectedIds, $ids);
    }
}
