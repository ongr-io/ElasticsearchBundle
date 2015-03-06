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

use ONGR\ElasticsearchBundle\DSL\Aggregation\CardinalityAggregation;

/**
 * Unit test for cardinality aggregation.
 */
class CardinalityAggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getArray method.
     */
    public function testGetArray()
    {
        $aggregation = new CardinalityAggregation('bar');

        // When $script is set.
        $aggregation->setScript('foo');
        $result = $aggregation->getArray();

        $this->assertArrayHasKey('script', $result);
        $this->assertEquals('foo', $result['script']);

        // When $field is set.
        $aggregation->setField('foo');
        $result = $aggregation->getArray();

        $this->assertArrayHasKey('field', $result);
        $this->assertEquals('foo', $result['field']);

        // When $precisionThreshold is set.
        $aggregation->setPrecisionThreshold(10);
        $result = $aggregation->getArray();

        $this->assertArrayHasKey('precision_threshold', $result);
        $this->assertEquals(10, $result['precision_threshold']);

        // When $rehash is set.
        $aggregation->setRehash(true);
        $result = $aggregation->getArray();

        $this->assertArrayHasKey('rehash', $result);
        $this->assertEquals(true, $result['rehash']);
    }

    /**
     * Tests if CardinalityAggregation#getArray throws exception when expected.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Cardinality aggregation must have field or script set.
     */
    public function testGetArrayException()
    {
        $aggregation = new CardinalityAggregation('bar');
        $aggregation->getArray();
    }


}
