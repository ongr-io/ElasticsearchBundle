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
use ONGR\ElasticsearchBundle\DSL\Filter\GeoPolygonFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Geo polygon filter functional test.
 */
class GeoPolygonFilterTest extends AbstractElasticsearchTestCase
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
                        'location' => ['lat' => 45.55, 'lon' => -70.66],
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGeoPolygonFilter.
     *
     * @return array
     */
    public function geoPolygonFilterData()
    {
        $out = [];

        // Case #0 lat lon as properties.
        $out[] = [
            new GeoPolygonFilter(
                'location',
                [
                    ['lat' => 20, 'lon' => -80],
                    ['lat' => 30, 'lon' => -40],
                    ['lat' => 70, 'lon' => -90],
                ]
            ),
            2,
        ];

        // Case #1 lat lon as string.
        $out[] = [new GeoPolygonFilter('location', ['20, -80', '30, -40', '70, -90']), 2];

        return $out;
    }

    /**
     * Test for geo distance range filter.
     *
     * @param BuilderInterface $filter
     * @param int              $expected
     *
     * @dataProvider geoPolygonFilterData
     */
    public function testGeoPolygonFilter($filter, $expected)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, count($results));
    }
}
