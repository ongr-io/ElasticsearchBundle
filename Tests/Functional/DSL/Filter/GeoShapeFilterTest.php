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

use ONGR\ElasticsearchBundle\DSL\Filter\GeoShapeFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GeoShapeFilterTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.4.0', '<'],
        ];
    }

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
                    [
                        '_id' => 2,
                        'title' => 'foo',
                        'price' => 10,
                        'shape' => ['type' => 'Point', 'coordinates' => [13.400544, 52.530286]],
                    ],
                    [
                        '_id' => 3,
                        'title' => 'bar',
                        'price' => 1000,
                        'shape' => ['type' => 'Point', 'coordinates' => [20.400543, -30.530286]],
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
     * Test geo-shape filter with pre-indexed data for expected search results.
     */
    public function testGeoShapePreIndexed()
    {
        $manager = $this->getManager();
        $index = $manager->getConnection()->getIndexName();
        $repo = $manager->getRepository('AcmeTestBundle:Product');
        $filter = new GeoShapeFilter();
        $filter->addPreIndexedShape('shape', 1, 'product', $index, 'shape');
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

    /**
     * Data provider for testGeoShapeProvided().
     *
     * @return array
     */
    public function getGeoShapeProvidedData()
    {
        $out = [];

        // Case #0 envelope type.
        $filterData = [
            'field' => 'shape',
            'coordinates' => [
                [13.0, 53.0],
                [14.0, 52.0],
            ],
            'type' => 'envelope',
            'parameters' => [],
        ];
        $expectedResult = [
            [
                'title' => 'foo',
                'price' => 10,
                'shape' => [
                    'type' => 'Point',
                    'coordinates' => [
                        13.400544,
                        52.530286,
                    ],
                ],
            ],
        ];
        $out[] = [$filterData, $expectedResult, []];

        // Case #1 polygon type.
        $filterData = [
            'field' => 'shape',
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
            'type' => 'polygon',
            'parameters' => [],
        ];
        $expectedResult = [
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
        $out[] = [$filterData, $expectedResult, []];

        // Case #2 circle type.
        $filterData = [
            'field' => 'shape',
            'type' => 'circle',
            'coordinates' => [4.89994, 52.37815],
            'parameters' => ['radius' => '1km'],
        ];

        $expectedResult = [
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

        $out[] = [$filterData, $expectedResult];

        return $out;
    }

    /**
     * Test geo-shape filter with provided data.
     *
     * @param array $filterData
     * @param array $expected
     *
     * @dataProvider getGeoShapeProvidedData
     */
    public function testGeoShapeProvided($filterData, $expected)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $filter = new GeoShapeFilter();
        $filter->addShape(
            $filterData['field'],
            $filterData['type'],
            $filterData['coordinates'],
            $filterData['parameters']
        );
        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }
}
