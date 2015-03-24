<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Filter;

use ONGR\ElasticsearchBundle\DSL\Filter\PostFilter;

class PostFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests GetType method.
     */
    public function testIfGetType()
    {
        $filter = new PostFilter();
        $this->assertEquals('post_filter', $filter->getType());
    }

    /**
     * Test if function is returning False.
     */
    public function testIfIsRelevantFunctionIsReturningFalse()
    {
        $bool = new PostFilter();
        $this->assertFalse($bool->isRelevant());
    }

    /**
     * Test addFilter method.
     */
    public function testAddFilter()
    {
        $filterMock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter')
            ->setMethods(['addToBool'])
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->once())
            ->method('addToBool')
            ->withAnyParameters();

        $filter = new PostFilter();
        $filter->setFilter($filterMock);
        $filter->addFilter($filterMock, 'test');
    }

    /**
     * Test setBoolParameters method.
     */
    public function testSetBoolParameters()
    {
        $filterMock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter')
            ->setMethods(['setParameters'])
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->once())
            ->method('setParameters')
            ->withAnyParameters();

        $filter = new PostFilter();
        $filter->setFilter($filterMock);
        $filter->setBoolParameters(['test_param']);
    }
}
