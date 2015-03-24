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

    /**
     * Tests addQuery method.
     */
    public function testAddQuery()
    {
        $missingFilter = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter')
            ->disableOriginalConstructor()
            ->setMethods(['addToBool'])
            ->getMock();
        $missingFilter->expects($this->once())
            ->method('addToBool')
            ->withAnyParameters();
        $postFilter = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\PostFilter')
            ->disableOriginalConstructor()
            ->getMock();

        $query = new Query();
        $query->setQuery($missingFilter);
        $query->addQuery($postFilter);
    }

    /**
     * Tests getType method.
     */
    public function testGetType()
    {
        $query = new Query();
        $this->assertEquals('query', $query->getType());
    }

    /**
     * Tests toArray method.
     */
    public function testToArray()
    {
        $missingFilter = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $missingFilter->expects($this->once())
            ->method('getType')
            ->willReturn('test_type');
        $missingFilter->expects($this->once())
            ->method('toArray')
            ->willReturn('test_array');

        $query = new Query();
        $query->setQuery($missingFilter);
        $this->assertEquals(['test_type' => 'test_array'], $query->toArray());
    }
}
