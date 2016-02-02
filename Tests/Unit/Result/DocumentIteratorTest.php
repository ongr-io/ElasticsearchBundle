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

use ONGR\ElasticsearchBundle\Result\DocumentIterator;

class DocumentIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for getAggregation() in case requested aggregation is not set.
     */
    public function testGetAggregationNull()
    {
        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new DocumentIterator([], $manager);

        $this->assertNull($iterator->getAggregation('foo'));
    }
}
