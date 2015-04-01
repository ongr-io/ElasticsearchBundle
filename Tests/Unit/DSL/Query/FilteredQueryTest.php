<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Query\FilteredQuery;
use ONGR\ElasticsearchBundle\Test\EncapsulationTestAwareTrait;

/**
 * Test for FilteredQuery.
 */
class FilteredQueryTest extends \PHPUnit_Framework_TestCase
{
    use EncapsulationTestAwareTrait;

    /**
     * Data provider for testToArray function.
     *
     * @return array
     */
    public function getArrayDataProvider()
    {
        $missingFilter = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $missingFilter->expects($this->any())
            ->method('toArray')
            ->willReturn(['testKey' => 'testValue']);

        return [
            [
                $missingFilter,
                [
                    'filter' => [
                        'bool' => [],
                    ],
                    'query' => [
                        'testKey' => 'testValue',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for filter toArray() method.
     *
     * @param Query $parameter Query for testing.
     * @param array $expected  Expected values.
     *
     * @dataProvider getArrayDataProvider
     */
    public function testToArray($parameter, $expected)
    {
        $filteredQuery = new FilteredQuery($parameter);
        $result = $filteredQuery->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $filteredQuery = new FilteredQuery();
        $this->assertEquals('filtered', $filteredQuery->getType());
    }

    /**
     * Returns list of fields to test. Works as data provider.
     *
     * @return array
     */
    public function getFieldsData()
    {
        return [
            ['query', 'ONGR\ElasticsearchBundle\DSL\BuilderInterface'],
        ];
    }

    /**
     * Returns entity class name.
     *
     * @return string
     */
    public function getClassName()
    {
        $this->setStub(new FilteredQuery());

        return 'ONGR\ElasticsearchBundle\DSL\Query\FilteredQuery';
    }

    /**
     * Tests getQuery method when query is not passed.
     */
    public function testGetQueryWithoutQuery()
    {
        $filteredQuery = new FilteredQuery();
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\DSL\Query\Query', $filteredQuery->getQuery());
    }
}
