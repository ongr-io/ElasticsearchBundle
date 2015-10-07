<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result\Aggregation;

use ONGR\ElasticsearchBundle\Result\Aggregation\HitsAggregationIterator;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Service\Repository;

class HitsAggregationIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests setting offset.
     *
     * @expectedException \BadMethodCallException
     */
    public function testOffsetSet()
    {
        $hits = new HitsAggregationIterator([], $this->getConverterMock(), $this->getRepositoryMock());
        $hits['foo'] = 'test';
    }

    /**
     * Tests unseting offset.
     *
     * @expectedException \BadMethodCallException
     */
    public function testOffsetUnset()
    {
        $hits = new HitsAggregationIterator([], $this->getConverterMock(), $this->getRepositoryMock());
        unset($hits['foo']);
    }

    /**
     * @return array
     */
    public function getTestIterationData()
    {
        $out = [];

        // Case #0: empty.
        $raw0 = [
            'hits' => [],
        ];
        $expected0 = [];
        $out[] = [
            $raw0,
            $expected0,
        ];

        // Case #1: has data.
        $raw1 = [
            'hits' => [
                [
                    '_id' => 'foo',
                    '_type' => 'customType',
                    '_source' => [],
                ],
                [
                    '_id' => 'baz',
                    '_type' => 'customType',
                    '_source' => [],
                ],
            ],
        ];
        $expected1 = [
            'foo',
            'baz',
        ];
        $out[] = [
            $raw1,
            $expected1,
        ];

        return $out;
    }

    /**
     * Tests hits iteration.
     *
     * @param array $raw
     * @param array $expected
     *
     * @dataProvider getTestIterationData
     */
    public function testIteration($raw, $expected)
    {
        $hits = new HitsAggregationIterator($raw, $this->getConverterMock(), $this->getRepositoryMock());

        $ids = [];
        foreach ($hits as $doc) {
            $ids[] = $doc->_id;
        }

        $this->assertEquals($expected, $ids);
    }

    /**
     * Tests count method.
     */
    public function testCount()
    {
        $hits = new HitsAggregationIterator(['total' => 4], $this->getConverterMock(), $this->getRepositoryMock());
        $this->assertEquals(4, $hits->count());
    }

    /**
     * Tests OffsetExists method.
     */
    public function testOffsetExists()
    {
        $hits = new HitsAggregationIterator(
            [
                'hits' => [[]],
            ],
            $this->getConverterMock(),
            $this->getRepositoryMock()
        );

        $this->assertTrue(isset($hits[0]), 'First offset should be set.');
        $this->assertFalse(isset($hits[1]), 'Second offset should not be set');
    }

    /**
     * @return Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConverterMock()
    {
        $converterMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->setMethods(['convertToDocument'])
            ->getMock();

        $converterMock
            ->expects($this->any())
            ->method('convertToDocument')
            ->will(
                $this->returnCallback(
                    function ($raw) {
                        return (object)array_merge(['_id' => $raw['_id']], $raw['_source']);
                    }
                )
            );

        return $converterMock;
    }

    /**
     * @return Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getRepositoryMock()
    {
        $repositoryMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Service\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        return $repositoryMock;
    }
}
