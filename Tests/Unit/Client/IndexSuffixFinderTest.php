<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Client;

use Ongr\ElasticsearchBundle\Client\Connection;
use Ongr\ElasticsearchBundle\Client\IndexSuffixFinder;

class IndexSuffixFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test cases for testFindNextFreeIndex.
     *
     * @return array
     */
    public function getFindNextFreeIndexCases()
    {
        $cases = [];

        $cases[] = [
            'foo-index',
            false,
            'foo-index-2001.01.30',
            new \DateTime('2001-01-30'),
        ];
        $cases[] = [
            'foo-index',
            true,
            'foo-index-2011.11.21-2',
            new \DateTime('2011-11-21'),
        ];

        $time = new \DateTime();
        $cases[] = [
            'bar-index',
            false,
            'bar-index-' . $time->format('Y.m.d'),
        ];

        return $cases;
    }

    /**
     * Test findNextFreeIndex method.
     *
     * @param string    $indexName
     * @param bool      $isOccupied
     * @param string    $expectedName
     * @param \DateTime $time
     *
     * @dataProvider getFindNextFreeIndexCases
     */
    public function testFindNextFreeIndex($indexName, $isOccupied, $expectedName, $time = null)
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMock('\Ongr\ElasticsearchBundle\Client\Connection', [], [], '', false);
        $connection->expects($this->any())->method('getIndexName')->willReturn($indexName);
        $connection->expects($this->any())->method('setIndexName');
        if ($isOccupied) {
            $connection->expects($this->any())->method('indexExists')->willReturnOnConsecutiveCalls(true, true, false);
        } else {
            $connection->expects($this->any())->method('indexExists')->willReturn(false);
        }

        $finder = new IndexSuffixFinder();
        $actualName = $finder->setNextFreeIndex($connection, $time);
        $this->assertEquals($expectedName, $actualName);
    }
}
