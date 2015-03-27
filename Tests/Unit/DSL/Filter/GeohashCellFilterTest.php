<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Filter;

use ONGR\ElasticsearchBundle\DSL\Filter\GeohashCellFilter;

class GeohashCellFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $filter = new GeohashCellFilter('test_field', 'test_location');
        $result = $filter->getType();
        $this->assertEquals('geohash_cell', $result);
    }

    /**
     * Data provider to testToArray.
     *
     * @return array
     */
    public function getArrayDataProvider()
    {
        return [
            // Case #1.
            ['pin', ['lat' => 13.4080, 'lon' => 52.5186], [], ['pin' => ['lat' => 13.4080, 'lon' => 52.5186]]],
            // Case #2.
            [
                'pin',
                ['lat' => 13.4080, 'lon' => 52.5186],
                [
                    'parameter1' => 'value1',
                    'parameter2' => 'value2',
                ],
                ['pin' => ['lat' => 13.4080, 'lon' => 52.5186], 'parameter1' => 'value1', 'parameter2' => 'value2'],
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param string $field      Field name.
     * @param array  $location   Cell location.
     * @param array  $parameters Optional parameters.
     * @param array  $expected   Expected result.
     *
     * @dataProvider getArrayDataProvider
     */
    public function testToArray($field, $location, $parameters, $expected)
    {
        $filter = new GeohashCellFilter($field, $location, $parameters);
        $result = $filter->toArray();
        $this->assertEquals($expected, $result);
    }
}
