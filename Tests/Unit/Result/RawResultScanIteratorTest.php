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

use ONGR\ElasticsearchBundle\Result\RawResultScanIterator;
use ONGR\ElasticsearchBundle\Service\Repository;

class RawResultScanIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for scan iterator.
     */
    public function testIterator()
    {
        $rawData = [
            'hits' => [
                'total' => 2,
                'hits' => [
                    ['_id' => 'foo'],
                ],
            ],
        ];

        $repository = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('scan')
            ->with('test_id', '5m', Repository::RESULTS_RAW)
            ->willReturn(
                [
                    '_scroll_id' => 'updated_id',
                    'hits' => [
                        'total' => 2,
                        'hits' => [
                            ['_id' => 'bar'],
                        ],
                    ],
                ]
            );

        $iterator = new RawResultScanIterator($rawData);
        $iterator->setRepository($repository)
            ->setScrollId('test_id')
            ->setScrollDuration('5m');

        $this->assertCount(2, $iterator);

        $data = [];
        $expectedData = [
            ['_id' => 'foo'],
            ['_id' => 'bar'],
        ];

        foreach ($iterator as $key => $document) {
            $data[$key] = $document;
        }

        $this->assertEquals($expectedData, $data);
    }
}
