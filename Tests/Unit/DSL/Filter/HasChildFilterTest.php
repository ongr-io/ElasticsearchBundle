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

use Ongr\ElasticsearchBundle\DSL\Filter\HasChildFilter;

class HasChildFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests GetType method.
     */
    public function testGetType()
    {
        $mock = $this->getMockBuilder('Ongr\ElasticsearchBundle\DSL\BuilderInterface')->getMock();
        $filter = new HasChildFilter('test_field', $mock);
        $result = $filter->getType();
        $this->assertEquals('has_child', $result);
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
            [
                'comment',
                'term',
                ['name' => 'foo'],
                [],
                'filter',
                ['type' => 'comment', 'filter' => ['term' => ['name' => 'foo']]],
            ],
            // Case #2.
            [
                'comment',
                'term',
                ['name' => 'foo'],
                ['parameter' => 'value'],
                'query',
                ['type' => 'comment', 'query' => ['term' => ['name' => 'foo']], 'parameter' => 'value'],
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param string $type         Child type.
     * @param string $queryType    Type of query for mock query class.
     * @param array  $queryToArray Return value for mock query class toArray method.
     * @param array  $parameters   Optional parameters.
     * @param string $dslType      Filter or query.
     * @param array  $expected     Expected result.
     *
     * @dataProvider getArrayDataProvider
     */
    public function testToArray($type, $queryType, $queryToArray, $parameters, $dslType, $expected)
    {
        $mockQuery = $this->getMockBuilder('Ongr\ElasticsearchBundle\DSL\BuilderInterface')->getMock();
        $mockQuery->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($queryType));
        $mockQuery->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($queryToArray));

        $filter = new HasChildFilter($type, $mockQuery, $parameters);
        $filter->setDslType($dslType);
        $result = $filter->toArray();
        $this->assertEquals($expected, $result);
    }
}
