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

use ONGR\ElasticsearchBundle\DSL\Aggregation\MissingAggregation;

class MissingAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test if exception is thrown when field is not set.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Missing aggregation must have a field set.
     */
    public function testIfExceptionIsThrown()
    {
        $agg = new MissingAggregation('test_agg');
        $agg->getArray();
    }
}
