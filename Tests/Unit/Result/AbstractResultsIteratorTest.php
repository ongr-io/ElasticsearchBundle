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

class AbstractResultsIteratorTest extends TestCase
{
    /**
     * Test if scroll is cleared on destructor.
     */
    public function testClearScroll()
    {
        $rawData = [
            '_scroll_id' => 'foo',
        ];

        $index = $this->getMockBuilder(IndexService::class)
            ->setMethods(['getConfig', 'clearScroll'])
            ->disableOriginalConstructor()
            ->getMock();
        $index->expects($this->any())->method('getConfig')->willReturn([]);
        $index->expects($this->once())->method('clearScroll')->with('foo');

        $scroll = ['_scroll_id' => 'foo', 'duration' => '5m'];
        $iterator = new RawIterator($rawData, $index, null, $scroll);

        // Trigger destructor call
        unset($iterator);
    }

    /**
     * Test for getDocumentScore().
     */
    public function testGetDocumentScore()
    {
        $rawData = [
            'hits' => [
                'total' => [
                    'value' => 3
                ],
                'hits' => [
                    [
                        '_index' => 'test',
                        '_id' => 'foo',
                        '_score' => 1,
                        '_source' => [
                            'title' => 'Product Foo',
                        ],
                    ],
                    [
                        '_index' => 'test',
                        '_id' => 'bar',
                        '_score' => 2,
                        '_source' => [
                            'title' => 'Product Bar',
                        ],
                    ],
                    [
                        '_index' => 'test',
                        '_id' => 'baz',
                        '_score' => null,
                        '_source' => [
                            'title' => 'Product Baz',
                        ],
                    ],
                ],
            ],
        ];

        $index = $this->getMockBuilder(IndexService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $results = new RawIterator($rawData, $index);

        $expectedScores = [1, 2, null];
        $actualScores = [];

        $this->assertEquals($rawData['hits']['total']['value'], $results->count());
        $this->assertEquals($rawData, $results->getRaw());

        foreach ($results as $item) {
            $actualScores[] = $item['_score'];
        }

        $this->assertEquals($expectedScores, $actualScores);
    }

    /**
     * Test for getDocumentScore() in case called when current iterator value is not valid.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Document score is available only while iterating over results
     */
    public function testGetScoreException()
    {
        $index = $this->getMockBuilder(IndexService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $results = new RawIterator([], $index);
        $results->getDocumentScore();
    }
}
