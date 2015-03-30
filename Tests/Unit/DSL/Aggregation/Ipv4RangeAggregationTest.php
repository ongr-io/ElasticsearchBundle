<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\DSL\Aggregation;

use Ongr\ElasticsearchBundle\DSL\Aggregation\Ipv4RangeAggregation;

class Ipv4RangeAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test exception when field and range are not set.
     *
     * @expectedException \LogicException
     */
    public function testIfExceptionIsThrownWhenFieldAndRangeAreNotSet()
    {
        $agg = new Ipv4RangeAggregation('foo');
        $agg->toArray();
    }
}
