<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\DSL\Filter;

use Ongr\ElasticsearchBundle\DSL\Filter\AndFilter;

class AndFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests GetType method.
     */
    public function testGetType()
    {
        $filter = new AndFilter('', []);
        $result = $filter->getType();
        $this->assertEquals('and', $result);
    }

    /**
     * Data provider for testToArray function.
     *
     * @return array
     */
    public function getArrayDataProvider()
    {
        $mockBuildeFfirstFilter = $this->getMockBuilder('Ongr\ElasticsearchBundle\DSL\BuilderInterface')
            ->getMock();
        $mockBuildeFfirstFilter->expects($this->any())
            ->method('getType')
            ->willReturn('term');
        $mockBuildeFfirstFilter->expects($this->any())
            ->method('toArray')
            ->willReturn(['test_field' => ['test_value' => 'test']]);

        $mockBuilderSecondFilter = $this->getMockBuilder('Ongr\ElasticsearchBundle\DSL\BuilderInterface')
            ->getMock();
        $mockBuilderSecondFilter->expects($this->any())
            ->method('getType')
            ->willReturn('prefix');
        $mockBuilderSecondFilter->expects($this->any())
            ->method('toArray')
            ->willReturn(['test_field' => ['test_value' => 'test']]);

        return [
            // Case #1.
            [
                [$mockBuildeFfirstFilter],
                [],
                [
                    'filters' => [
                        0 => [
                            'term' => [
                                'test_field' => [
                                    'test_value' => 'test',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Case #2.
            [
                [$mockBuildeFfirstFilter, $mockBuilderSecondFilter],
                [],
                [
                    'filters' => [
                        0 => [
                            'term' => [
                                'test_field' => [
                                    'test_value' => 'test',
                                ],
                            ],
                        ],
                        1 => [
                            'prefix' => [
                                'test_field' => [
                                    'test_value' => 'test',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Case #3.
            [
                [$mockBuildeFfirstFilter, $mockBuilderSecondFilter],
                ['type' => 'acme'],
                [
                    'filters' => [
                        0 => [
                            'term' => [
                                'test_field' => [
                                    'test_value' => 'test',
                                ],
                            ],
                        ],
                        1 => [
                            'prefix' => [
                                'test_field' => [
                                    'test_value' => 'test',
                                ],
                            ],
                        ],
                    ],
                    'type' => 'acme',
                ],
            ],
        ];
    }

    /**
     * Test for filter toArray() method.
     *
     * @param BuilderInterface[] $filters    Array.
     * @param array              $parameters Optional parameters.
     * @param array              $expected   Expected values.
     *
     * @dataProvider getArrayDataProvider
     */
    public function testToArray($filters, $parameters, $expected)
    {
        $filter = new AndFilter($filters, $parameters);
        $result = $filter->toArray();
        $this->assertEquals($expected, $result);
    }
}
