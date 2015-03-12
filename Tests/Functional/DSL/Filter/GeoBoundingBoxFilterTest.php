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

use ONGR\ElasticsearchBundle\DSL\Filter\GeoBoundingBoxFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GeoBoundingBoxFilterTest extends AbstractElasticsearchTestCase
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
                        'location' => ['lat' => 40.12, 'lon' => - 71.34],
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'location' => ['lat' => 55.55, 'lon' => - 66.66],
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'location' => ['lat' => 123.55, 'lon' => - 166.66],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGeoBoundingBoxFilter.
     *
     * @return array
     */
    public function geoBoundingBoxFilterData()
    {
        $out = [];

        // Case #0 2 points, Lat Lon As Properties.
        $filterData = [['lat' => 40.73, 'lon' => - 74.1], ['lat' => 40.01, 'lon' => - 71.12]];
        $expected = [
            [
                'title' => 'foo',
                'price' => 10,
                'location' => [
                    'lat' => 40.12,
                    'lon' => - 71.34,
                ],
                'description' => 'Lorem ipsum',
            ],
        ];

        $out[] = [$filterData, $expected];

        // Case #1 4 points coordinates.
        $filterData = [40.73, - 74.1, - 71.12, 40.01];
        $expected = [
            [
                'title' => 'foo',
                'price' => 10,
                'location' => [
                    'lat' => 40.12,
                    'lon' => - 71.34,
                ],
                'description' => 'Lorem ipsum',
            ],
        ];

        $out[] = [$filterData, $expected];

        return $out;
    }

    /**
     * Test for geo bounding box filter.
     *
     * @param array $filterData
     * @param array $expected
     *
     * @dataProvider geoBoundingBoxFilterData
     */
    public function testGeoBoundingBoxFilter($filterData, $expected)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $filter = new GeoBoundingBoxFilter('location', $filterData);
        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
