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

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Filter\GeoDistanceRangeFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Geo distance Range filter functional test.
 */
class GeoDistanceRangeFilterTest extends AbstractElasticsearchTestCase
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
                        'location' => ['lat' => 40.12, 'lon' => -70.34],
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'location' => ['lat' => 55.55, 'lon' => -66.66],
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'location' => ['lat' => 123.55, 'lon' => -166.66],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'foo baz',
                        'price' => 200,
                        'location' => ['lat' => 45.55, 'lon' => -80.66],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                    [
                        '_id' => 5,
                        'title' => 'foofoo',
                        'price' => 10,
                        'location' => ['lat' => 40, 'lon' => -70],
                        'description' => 'Lorem ipsum',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGeoDistanceRangeFilter.
     *
     * @return array
     */
    public function geoDistanceRangeFilterData()
    {
        $out = [];

        // Case #0 lat lon as properties.
        $out[] = [
            new GeoDistanceRangeFilter('location', ['from' => '10km', 'to' => '400km'], ['lat' => 40, 'lon' => -70]),
            1,
        ];

        // Case #1 lat lon as array and distance_type = plane.
        $out[] = [
            new GeoDistanceRangeFilter('location', ['gt' => '10km', 'lt' => '4000km'], ['lat' => 40, 'lon' => -70]),
            3,
        ];

        return $out;
    }

    /**
     * Test for geo distance range filter.
     *
     * @param BuilderInterface $filter
     * @param int              $expected
     *
     * @dataProvider geoDistanceRangeFilterData
     */
    public function testGeoDistanceRangeFilter($filter, $expected)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, count($results));
    }
}
