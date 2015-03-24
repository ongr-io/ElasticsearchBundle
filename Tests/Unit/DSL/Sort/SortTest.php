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

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Sort\AbstractSort;
use ONGR\ElasticsearchBundle\DSL\Sort\Sort;

class SortTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $sort = new Sort('name');
        $result = $sort->getType();
        $this->assertEquals('name', $result);
    }

    /**
     * Return mock object to be passed as nestedFilter parameter to testToArray method.
     *
     * @param string $getType Return value of getType method.
     * @param array  $toArray Return value of toArray method.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getNestedFilterMock($getType, $toArray)
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\BuilderInterface')->getMock();
        $mock->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($getType));
        $mock->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($toArray));

        return $mock;
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
                ['order' => AbstractSort::ORDER_ASC],
                'name',
            ],
            // Case #2.
            [
                [
                    'order' => AbstractSort::ORDER_DESC,
                    'mode' => AbstractSort::MODE_AVG,
                    'nested_filter' => ['user' => ['order' => AbstractSort::ORDER_ASC]],
                ],
                'name',
                AbstractSort::ORDER_DESC,
                $this->getNestedFilterMock('user', ['order' => AbstractSort::ORDER_ASC]),
                AbstractSort::MODE_AVG,
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param array            $expected     Expected result.
     * @param string           $field        Field name.
     * @param string           $order        Order direction.
     * @param BuilderInterface $nestedFilter Filter for sorting.
     * @param string           $mode         Multi-valued field sorting mode [MODE_MIN, MODE_MAX, MODE_AVG, MODE_SUM].
     *
     * @dataProvider getTestToArrayData
     */
    public function testToArray(
        $expected,
        $field,
        $order = AbstractSort::ORDER_ASC,
        BuilderInterface $nestedFilter = null,
        $mode = null
    ) {
        $sort = new Sort($field, $order, $nestedFilter, $mode);
        $result = $sort->toArray();
        $this->assertEquals($expected, $result);
    }
}
