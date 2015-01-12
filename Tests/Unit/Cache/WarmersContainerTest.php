<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Cache;

use ONGR\ElasticsearchBundle\Cache\WarmersContainer;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\DSL\Search;

class WarmersContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if warmers container works as expected.
     */
    public function testWarmersContainer()
    {
        $container = new WarmersContainer();
        $container->setWarmers($this->getWarmers());
        $this->assertEquals(
            [
                'warmer1' => [],
                'warmer2' => [],
            ],
            $container->getWarmers()
        );
    }

    /**
     * Returns mocked warmers array.
     *
     * @return array
     */
    private function getWarmers()
    {
        $warmer1 = $this->getMock('ONGR\ElasticsearchBundle\Cache\WarmerInterface');
        $warmer1
            ->expects($this->once())
            ->method('warmUp')
            ->with(new Search());
        $warmer1
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('warmer1'));

        $warmer2 = $this->getMock('ONGR\ElasticsearchBundle\Cache\WarmerInterface');
        $warmer2
            ->expects($this->once())
            ->method('warmUp')
            ->with(new Search());
        $warmer2
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('warmer2'));

        return [$warmer1, $warmer2];
    }
}
