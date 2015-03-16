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

use ONGR\ElasticsearchBundle\DSL\Aggregation\GeoBoundsAggregation;
use ONGR\ElasticsearchBundle\DSL\Query\MatchQuery;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Geo bounds aggregation test.
 */
class GeoBoundsAggregationTest extends AbstractElasticsearchTestCase
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
     * Test for geo bounds aggregation with match query.
     */
    public function testGeoBoundsAggregation()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $query = new MatchQuery('price', 100);

        $agg = new GeoBoundsAggregation('test_agg');
        $agg->setWrapLongitude(false);
        $agg->setField('location');
        $search = $repo->createSearch()->addQuery($query);
        $search->addAggregation($agg);

        $results = $repo->execute($search, $repo::RESULTS_RAW)['aggregations'];
        $expectedResult = [
            'agg_test_agg' => [
                'bounds' => [
                    'top_left' => [
                        'lat' => 55.55,
                        'lon' => -80.66,
                    ],
                    'bottom_right' => [
                        'lat' => 40,
                        'lon' => -66.66,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $results);
    }
}
