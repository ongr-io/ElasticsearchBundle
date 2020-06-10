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

        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->setMethods(['getConfig', 'clearScroll'])
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())->method('getConfig')->willReturn([]);
        $manager->expects($this->once())->method('clearScroll')->with('foo');

        $scroll = ['_scroll_id' => 'foo', 'duration' => '5m'];
        $iterator = new DummyIterator($rawData, $manager, $scroll);

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
                'total' => 3,
                'hits' => [
                    [
                        '_index' => 'test',
                        '_type' => 'product',
                        '_id' => 'foo',
                        '_score' => 1,
                        '_source' => [
                            'title' => 'Product Foo',
                        ],
                    ],
                    [
                        '_index' => 'test',
                        '_type' => 'product',
                        '_id' => 'bar',
                        '_score' => 2,
                        '_source' => [
                            'title' => 'Product Bar',
                        ],
                    ],
                    [
                        '_index' => 'test',
                        '_type' => 'product',
                        '_id' => 'baz',
                        '_score' => null,
                        '_source' => [
                            'title' => 'Product Baz',
                        ],
                    ],
                ],
            ],
        ];

        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $results = new DummyIterator($rawData, $manager);

        $expectedScores = [1, 2, null];
        $actualScores = [];

        foreach ($results as $item) {
            $actualScores[] = $results->getDocumentScore();
        }

        $this->assertEquals($expectedScores, $actualScores);
    }

    /**
     * Test for getDocumentScore() in case called when current iterator value is not valid.
     */
    public function testGetScoreException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Document score is available only while iterating over results');

        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $results = new DummyIterator([], $manager);
        $results->getDocumentScore();
    }
}
