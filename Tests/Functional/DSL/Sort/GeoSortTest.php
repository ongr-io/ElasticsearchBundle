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

use ONGR\ElasticsearchBundle\DSL\Sort\GeoSort;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class GeoSortTest extends ElasticsearchTestCase
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
                        'location' => [10, 10],
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'location' => [30, 30],
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'location' => [20, 20],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider testGeoSort.
     *
     * @return array
     */
    public function geoSortData()
    {
        $out = [];

        // Case #0 simple sort.
        $sorts[] = [
            'field' => 'location',
            'order' => GeoSort::ORDER_DESC,
            'loc'  => [0, 0],
            'unit' => null,
            'mode' => null
        ];
        $expectedIds = [2, 3, 1];
        $expectedDistances = [4608667.8658243, 3115618.3302211, 1570237.8876592];
        $out[] = [$sorts, $expectedIds, $expectedDistances];

        // Case #1 using miles.
        $sorts[] = [
            'field' => 'location',
            'order' => GeoSort::ORDER_DESC,
            'loc'  => [0, 0],
            'unit' => 'mi',
            'mode' => null
        ];
        $expectedIds = [2, 3, 1];
        $expectedDistances = [2863.6934464131, 1935.955476406, 975.70058835102];
        $out[] = [$sorts, $expectedIds, $expectedDistances];

        // Case #2 using mode.
        $sorts[] = [
            'field' => 'location',
            'order' => GeoSort::ORDER_ASC,
            'loc'  => [22, 22],
            'unit' => 'km',
            'mode' => GeoSort::MODE_AVG
        ];
        $expectedIds = [3, 2, 1];
        $expectedDistances = [304.44075552441, 1195.8066938337, 1850.2997852109];
        $out[] = [$sorts, $expectedIds, $expectedDistances];

        return $out;
    }

    /**
     * Check if simple sorting works as expected.
     *
     * @param array $sorts
     * @param array $expectedIds
     * @param array $expectedDistances
     *
     * @dataProvider geoSortData()
     */
    public function testGeoSort($sorts, $expectedIds, $expectedDistances)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch();
        foreach ($sorts as $sort) {
            $search->addSort(new GeoSort($sort['field'], $sort['loc'], $sort['order'], $sort['unit'], $sort['mode']));
        }
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        // Compare arrangement.
        $ids = [];
        $distances = [];
        foreach ($results['hits']['hits'] as $result) {
            $ids[] = $result['_id'];
            $distances[] = $result['sort'][0];
        }
        $this->assertEquals($expectedIds, $ids);
        $this->assertEquals($expectedDistances, $distances, 'Distances are different.', 0.0001);
    }
}
