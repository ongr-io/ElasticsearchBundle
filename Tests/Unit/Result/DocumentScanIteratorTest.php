<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Result;

use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Result\DocumentScanIterator;

class DocumentScanIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for Document Scan iterator.
     */
    public function testIterator()
    {
        $rawData = [
            'hits' => [
                'total' => 2,
                'hits' => [],
            ],
        ];

        $repository = $this->getMockBuilder('Ongr\ElasticsearchBundle\ORM\Repository')
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
                        'hits' => [['id' => 1]],
                    ],
                ]
            );

        $iterator = new DocumentScanIterator($rawData, [], []);
        $iterator->setRepository($repository)
            ->setScrollId('test_id')
            ->setScrollDuration('5m');

        $this->assertCount(2, $iterator);

        $this->assertTrue($iterator->valid());
    }
}
