<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result;

use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use PHPUnit\Framework\TestCase;

class RawIteratorTest extends TestCase
{
    /**
     * Test for getAggregations().
     */
    public function testGetAggregations()
    {
        $rawData = [
            'aggregations' => [
                'avg_grade' => [
                    'value' => 75,
                ],
            ],
        ];

        $index = $this->getMockBuilder(IndexService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new RawIterator($rawData, $index);

        $this->assertEquals($rawData['aggregations'], $iterator->getAggregations());
    }

    /**
     * Tests iterator.
     */
    public function testIterator()
    {
        $rawData = [
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_index' => 'test',
                        '_id' => 'foo',
                        '_score' => 1,
                        '_source' => [
                            'title' => 'Product Foo',
                        ],
                    ],
                ],
            ],
        ];

        $index = $this->getMockBuilder(IndexService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new RawIterator($rawData, $index);

        $this->assertEquals($rawData['hits']['hits'][0], $iterator->current());
    }
}
