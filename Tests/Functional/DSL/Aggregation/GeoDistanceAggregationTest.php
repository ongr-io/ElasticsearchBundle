<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Aggregation;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\DSL\Aggregation\GeoDistanceAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;

class GeoDistanceAggregationTest extends AbstractElasticsearchTestCase
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
                        'price' => 100,
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
                        'price' => 10,
                        'location' => ['lat' => 123.55, 'lon' => -166.66],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGeoDistanceAggregation().
     *
     * @return array
     */
    public function getGeoDistanceAggregationData()
    {
        $out = [];

        // Case #0 unit: km, origin: string.
        $queryData = [
            'field' => 'location',
            'range' => [null, 1000],
            'unit' => 'km',
            'origin' => '50, -70',
        ];
        $expected = [
            [
                'key' => '*-1000.0',
                'from' => 0,
                'to' => 1000,
                'doc_count' => 1,
            ],
        ];
        $out[] = [$queryData, $expected];

        // Case #1 unit: mi, origin: array.
        $queryData = [
            'field' => 'location',
            'range' => [null, 1000],
            'unit' => 'mi',
            'origin' => [ - 70, 50],
        ];
        $expected = [
            [
                'key' => '*-1000.0',
                'from' => 0,
                'to' => 1000,
                'doc_count' => 2,
            ],
        ];
        $out[] = [$queryData, $expected];

        // Case #2 unit: mi, origin: associative array, distance_type: plane.
        $queryData = [
            'field' => 'location',
            'range' => [null, 1000],
            'unit' => 'mi',
            'origin' => ['lon' => -70, 'lat' => 50],
            'distance_type' => 'plane',
        ];
        $expected = [
            [
                'key' => '*-1000.0',
                'from' => 0,
                'to' => 1000,
                'doc_count' => 2,
            ],
        ];
        $out[] = [$queryData, $expected];

        return $out;
    }

    /**
     * Test for geo distance aggregation.
     *
     * @param array $queryData
     * @param array $expectedResults
     *
     * @dataProvider getGeoDistanceAggregationData
     */
    public function testGeoDistanceAggregation($queryData, $expectedResults)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $agg = new GeoDistanceAggregation('test_agg');
        $agg->setField($queryData['field']);
        $agg->setUnit($queryData['unit']);
        $agg->addRange($queryData['range'][0], $queryData['range'][1]);
        $agg->setOrigin($queryData['origin']);
        if (array_key_exists('distance_type', $queryData)) {
            $agg->setDistanceType($queryData['distance_type']);
        }
        $search = $repo->createSearch()->addAggregation($agg);
        $results = $repo->execute($search, Repository::RESULTS_RAW)['aggregations']['agg_test_agg'];
        $this->assertEquals($expectedResults, $results['buckets']);
    }
}
