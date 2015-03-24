<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Cache;

use Ongr\ElasticsearchBundle\Cache\WarmersContainer;
use Ongr\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use Ongr\ElasticsearchBundle\DSL\Search;

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
        $warmer1 = $this->getMock('Ongr\ElasticsearchBundle\Cache\WarmerInterface');
        $warmer1
            ->expects($this->once())
            ->method('warmUp')
            ->with(new Search());
        $warmer1
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('warmer1'));

        $warmer2 = $this->getMock('Ongr\ElasticsearchBundle\Cache\WarmerInterface');
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
