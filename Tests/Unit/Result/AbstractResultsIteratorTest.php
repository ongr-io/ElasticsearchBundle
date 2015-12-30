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

class AbstractResultsIteratorTest extends \PHPUnit_Framework_TestCase
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
}
