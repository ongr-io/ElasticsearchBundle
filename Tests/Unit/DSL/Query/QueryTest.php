<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Query\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests setBoolParameters method.
     */
    public function testSetBoolParameters()
    {
        $missingFilter = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter')
            ->setConstructorArgs(['test_field'])
            ->getMock();

        $missingFilter->expects($this->once())
            ->method('setParameters');

        $query = new Query();

        $query->setQuery($missingFilter);

        $query->setBoolParameters([false]);
    }
}
