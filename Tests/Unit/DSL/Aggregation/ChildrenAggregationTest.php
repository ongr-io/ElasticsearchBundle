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

use Ongr\ElasticsearchBundle\DSL\Aggregation\ChildrenAggregation;

/**
 * Unit test for children aggregation.
 */
class ChildrenAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if ChildrenAggregation#getArray throws exception when expected.
     *
     * @expectedException \LogicException
     */
    public function testGetArrayException()
    {
        $aggregation = new ChildrenAggregation('foo');
        $aggregation->getArray();
    }
}
