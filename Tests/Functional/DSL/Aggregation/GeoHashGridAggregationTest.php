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

use ONGR\ElasticsearchBundle\DSL\Aggregation\GeoHashGridAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GeoHashGridAggregationTest extends AbstractElasticsearchTestCase
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
                    [
                        '_id' => 4,
                        'title' => 'foo baz',
                        'price' => 100,
                        'location' => ['lat' => 45.55, 'lon' => -80.66],
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                    [
                        '_id' => 5,
                        'title' => 'foofoo',
                        'price' => 100,
                        'location' => ['lat' => 40, 'lon' => -70],
                        'description' => 'Lorem ipsum',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test geohash grid aggregation with default precision.
     */
    public function testGeoHashGridAggregationWithDefaultPrecision()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $agg = new GeoHashGridAggregation('test_agg');
        $agg->setField('location');

        $search = $repo->createSearch()->addAggregation($agg);
        $results = $repo->execute($search, Repository::RESULTS_RAW)['aggregations']['agg_test_agg'];
        $this->assertEquals(5, count($results['buckets']));
    }

    /**
     * Test geohash grid with multiple parameters.
     */
    public function testGeoHashGridAggregationWithAditionalParameters()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $agg = new GeoHashGridAggregation('test_agg');
        $agg->setField('location');
        $agg->setPrecision(2);
        $agg->setSize(2);
        $agg->setShardSize(1);

        $search = $repo->createSearch()->addAggregation($agg);
        $results = $repo->execute($search, Repository::RESULTS_RAW)['aggregations']['agg_test_agg'];
        $expectedResults = [
            'buckets' => [
                [
                    'key' => 'dr',
                    'doc_count' => 2,
                ],
                [
                    'key' => 'u6',
                    'doc_count' => 1,
                ],
            ],
        ];
        $this->assertEquals($expectedResults, $results);
    }
}
