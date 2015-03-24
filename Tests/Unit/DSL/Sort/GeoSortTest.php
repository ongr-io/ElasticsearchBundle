<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Sort;

use ONGR\ElasticsearchBundle\DSL\Sort\AbstractSort;
use ONGR\ElasticsearchBundle\DSL\Sort\GeoSort;

class GeoSortTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $geoSort = new GeoSort('location', '-70,40');
        $result = $geoSort->getType();
        $this->assertEquals('_geo_distance', $result);
    }

    /**
     * Data provider for testToArray().
     *
     * @return array
     */
    public function getTestToArrayData()
    {
        return [
            // Case #1.
            [
                ['location' => '-70,40', 'order' => AbstractSort::ORDER_ASC],
                'location',
                '-70,40',
            ],
            // Case #2.
            [
                [
                    'location' => ['lat' => 40, 'lon' => -70],
                    'order' => AbstractSort::ORDER_DESC,
                    'mode' => AbstractSort::MODE_SUM,
                    'unit' => 'km',
                ],
                'location',
                ['lat' => 40, 'lon' => -70],
                AbstractSort::ORDER_DESC,
                'km',
                AbstractSort::MODE_SUM,
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param array        $expected Expected result.
     * @param string       $field    Field name.
     * @param array|string $location Possible types examples:
     *                               [-70, 40]
     *                               ["lat" : 40, "lon" : -70]
     *                               "-70,40".
     * @param string       $order    Order.
     * @param string       $unit     Units for measuring the distance.
     * @param string       $mode     Mode.
     *
     * @dataProvider getTestToArrayData
     */
    public function testToArray(
        $expected,
        $field,
        $location,
        $order = AbstractSort::ORDER_ASC,
        $unit = null,
        $mode = null
    ) {
        $geoSort = new GeoSort($field, $location, $order, $unit, $mode);
        $result = $geoSort->toArray();
        $this->assertEquals($expected, $result);
    }
}
