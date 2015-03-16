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

use ONGR\ElasticsearchBundle\DSL\Filter\GeoShapePreIndexed;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GeoShapePreIndexedTest extends AbstractElasticsearchTestCase
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
                        'title' => 'foo bar',
                        'shape' => [
                            'type' => 'polygon',
                            'coordinates' => [
                                [
                                    [4.89218, 52.37356],
                                    [4.89205, 52.37276],
                                    [4.89301, 52.37274],
                                    [4.89392, 52.37250],
                                    [4.89431, 52.37287],
                                    [4.89331, 52.37346],
                                    [4.89305, 52.37326],
                                    [4.89218, 52.37356],
                                ],
                            ],
                        ],
                    ],
                ],
                'neighbourhood' => [
                    [
                        '_id' => 2,
                        'title' => 'foo',
                        'shape' => [
                            'type' => 'polygon',
                            'coordinates' => [
                                [
                                    [4.88330, 52.38617],
                                    [4.87463, 52.37254],
                                    [4.87875, 52.36369],
                                    [4.88939, 52.35850],
                                    [4.89840, 52.35755],
                                    [4.91909, 52.36217],
                                    [4.92656, 52.36594],
                                    [4.93368, 52.36615],
                                    [4.93342, 52.37275],
                                    [4.92690, 52.37632],
                                    [4.88330, 52.38617],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test Geo Shape pre-indexed for expected search results.
     */
    public function testGeoShapePreIndexed()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $filter = new GeoShapePreIndexed('shape', 1, 'product', 'ongr-elasticsearch-bundle-default-test', 'shape');
        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expected = [
            [
                'title' => 'foo bar',
                'shape' => [
                    'type' => 'polygon',
                    'coordinates' => [
                        [
                            [4.89218, 52.37356],
                            [4.89205, 52.37276],
                            [4.89301, 52.37274],
                            [4.89392, 52.37250],
                            [4.89431, 52.37287],
                            [4.89331, 52.37346],
                            [4.89305, 52.37326],
                            [4.89218, 52.37356],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }
}
