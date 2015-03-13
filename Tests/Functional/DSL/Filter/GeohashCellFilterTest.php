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

use ONGR\ElasticsearchBundle\DSL\Filter\GeohashCellFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GeohashCellFilterTest extends AbstractElasticsearchTestCase
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
                        'location' => ['lat' => 50.719, 'lon' => -83.999],
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'location' => ['lat' => 40.716, 'lon' => -73.981],
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'location' => ['lat' => 40.711, 'lon' => -73.980],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGeohashCellFilter().
     *
     * @return array
     */
    public function getGeohashCellFilterData()
    {
        $out = [];

        // Case #0 cell is defined by a latitude and longitude.
        $filterData = [
            'field' => 'location',
            'points' => [
                'lat' => 40.718,
                'lon' => -73.983,
            ],
            'parameters' => [
                'precision' => '8km',
            ],
        ];
        $expected = [
            [
                'title' => 'bar',
                'price' => 100,
                'location' => [
                    'lat' => 40.716,
                    'lon' => -73.981,
                ],
                'description' => 'Lorem ipsum dolor sit amet...',
            ],
            [
                'title' => 'baz',
                'price' => 1000,
                'location' => [
                    'lat' => 40.711,
                    'lon' => -73.98,
                ],
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
            ],
        ];

        $out[] = [$filterData, $expected];

        // Case #1 cell is defined by a geohash.
        $filterData = [
            'field' => 'location',
            'points' => 'dr5r',
            'parameters' => [],
        ];

        $out[] = [$filterData, $expected];

        return $out;
    }

    /**
     * Test for geohash cell filter.
     *
     * @param array $filterData
     * @param array $expected
     *
     * @dataProvider getGeohashCellFilterData
     */
    public function testGeohashCellFilter($filterData, $expected)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $geohashCell = new GeohashCellFilter($filterData['field'], $filterData['points'], $filterData['parameters']);
        $search = $repo->createSearch()->addFilter($geohashCell);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
