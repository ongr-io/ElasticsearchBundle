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
use ONGR\ElasticsearchBundle\DSL\Sort\ScriptSort;

/**
 * Unit test for ScriptSort.
 */
class ScriptSortTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $scriptSort = new ScriptSort('doc[\'field_name\'].value * factor', 'number');
        $result = $scriptSort->getType();
        $this->assertEquals('_script', $result);
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
                [
                    'script' => 'doc[\'field_name\'].value * factor',
                    'type' => 'number',
                    'order' => AbstractSort::ORDER_DESC,
                ],
                'doc[\'field_name\'].value * factor',
                'number',
            ],
            // Case #2.
            [
                [
                    'script' => 'doc[\'field_name\'].value * factor',
                    'type' => 'number',
                    'order' => AbstractSort::ORDER_ASC,
                    'params' => ['factor' => 1.1],
                ],
                'doc[\'field_name\'].value * factor',
                'number',
                ['factor' => 1.1],
                AbstractSort::ORDER_ASC,
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param array  $expected   Expected result.
     * @param string $script     Script.
     * @param string $returnType Return type.
     * @param array  $params     Additional parameters.
     * @param string $order      Sorting order.
     *
     * @dataProvider getTestToArrayData
     */
    public function testToArray($expected, $script, $returnType, $params = null, $order = AbstractSort::ORDER_DESC)
    {
        $scriptSort = new ScriptSort($script, $returnType, $params, $order);
        $result = $scriptSort->toArray();
        $this->assertEquals($expected, $result);
    }
}
