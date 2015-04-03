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
use ONGR\ElasticsearchBundle\DSL\Sort\Sorts;

/**
 * Unit test for Sorts.
 */
class SortsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $sorts = new Sorts();
        $result = $sorts->getType();
        $this->assertEquals('sort', $result);
    }

    /**
     * Return mock object of AbstractSort class.
     *
     * @param string $getType    Return value of getType method.
     * @param array  $toArray    Return value of toArray method.
     * @param int    $callsCount How many times getType and getArray methods will be called.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getAbstractSortMock($getType, $toArray, $callsCount = 1)
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Sort\AbstractSort')
            ->setConstructorArgs(['field', AbstractSort::ORDER_ASC, AbstractSort::MODE_AVG])
            ->getMock();
        $mock->expects($this->exactly($callsCount))
            ->method('getType')
            ->will($this->returnValue($getType));
        $mock->expects($this->exactly($callsCount))
            ->method('toArray')
            ->will($this->returnValue($toArray));

        return $mock;
    }

    /**
     * Data provider for testIsRelevant().
     *
     * @return array
     */
    public function getTestIsRelevantData()
    {
        return [
            [true, [$this->getAbstractSortMock('sort', [])]],
            [false, []],
        ];
    }

    /**
     * Tests isRelevant method.
     *
     * @param bool           $expected   Expected result.
     * @param AbstractSort[] $sortsArray Sorts collection.
     *
     * @dataProvider getTestIsRelevantData
     */
    public function testIsRelevant($expected, $sortsArray)
    {
        $sorts = new Sorts();
        foreach ($sortsArray as $sort) {
            $sorts->addSort($sort);
        }
        $this->assertEquals($expected, $sorts->isRelevant());
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
                    'user' => ['order' => AbstractSort::ORDER_ASC],
                    'score' => ['order' => AbstractSort::ORDER_DESC],
                ],
                [
                    $this->getAbstractSortMock('user', ['order' => AbstractSort::ORDER_ASC], 2),
                    $this->getAbstractSortMock('score', ['order' => AbstractSort::ORDER_DESC], 2),
                ],
            ],
            // Case #2.
            [
                [],
                [],
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param array          $expected   Expected result.
     * @param AbstractSort[] $sortsArray Sorts collection.
     *
     * @dataProvider getTestToArrayData
     */
    public function testToArray($expected, $sortsArray)
    {
        $sorts = new Sorts();
        foreach ($sortsArray as $sort) {
            $sorts->addSort($sort);
        }
        $result = $sorts->toArray();
        $this->assertEquals($expected, $result);
    }
}
