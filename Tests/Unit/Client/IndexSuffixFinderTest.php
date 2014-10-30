<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Client;

use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Client\IndexSuffixFinder;

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

        $cases[] = ['foo-index', false, 'foo-index-2001.01.30'];
        $cases[] = ['foo-index', true, 'foo-index-2001.01.30-2'];

        return $cases;
    }

    /**
     * Test findNextFreeIndex method.
     *
     * @param string $indexName
     * @param bool   $isOccupied
     * @param string $expectedName
     *
     * @dataProvider getFindNextFreeIndexCases
     */
    public function testFindNextFreeIndex($indexName, $isOccupied, $expectedName)
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMock('\ONGR\ElasticsearchBundle\Client\Connection', [], [], '', false);
        $connection->expects($this->any())->method('getIndexName')->willReturn($indexName);
        $connection->expects($this->any())->method('setIndexName');
        if ($isOccupied) {
            $connection->expects($this->any())->method('indexExists')->willReturnOnConsecutiveCalls(true, true, false);
        } else {
            $connection->expects($this->any())->method('indexExists')->willReturn(false);
        }

        $finder = new IndexSuffixFinder();
        $actualName = $finder->setNextFreeIndex($connection, new \DateTime('2001-01-30'));
        $this->assertEquals($expectedName, $actualName);
    }
}
