<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Aggregation;

use ONGR\ElasticsearchBundle\DSL\Aggregation\HistogramAggregation;

class HistogramAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if exception is thrown.
     *
     * @expectedException \LogicException
     */
    public function testHistogramAggregationException()
    {
        $agg = new HistogramAggregation('foo');
        $agg->toArray();
    }
}
