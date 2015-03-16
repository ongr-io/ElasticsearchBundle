<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Document;

/**
 * Tests AbstractDocument.
 *
 * Class AbstractDocumentTest
 *
 * @package ONGR\ElasticsearchBundle\Tests\Unit\Document
 */
class AbstractDocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests __set and __get methods.
     */
    public function testSetGet()
    {
        /** @var \ONGR\ElasticsearchBundle\Document\AbstractDocument $stub */
        $stub = $this->getMockForAbstractClass('\ONGR\ElasticsearchBundle\Document\AbstractDocument');
        $stub->__set('_id', '123');
        $stub->__set('_score', '123');
        $stub->__set('_ttl', '123');
        $stub->__set('_parent', '123');
        $stub->__set('foo', '123');

        $this->assertEquals('123', $stub->__get('_id'));
        $this->assertEquals('123', $stub->__get('_score'));
        $this->assertEquals('123', $stub->__get('_ttl'));
        $this->assertEquals('123', $stub->__get('_parent'));
        $this->assertEquals(null, $stub->__get('foo'));
        $this->assertEquals('123', $stub->getScore());
        $this->assertTrue($stub->hasParent());
    }

    /**
     * Tests if getHighlight() throws Exception.
     *
     * @expectedException        \UnderflowException
     * @expectedExceptionMessage Highlight not set.
     */
    public function testGetHighlightException()
    {
        /** @var \ONGR\ElasticsearchBundle\Document\AbstractDocument $stub */
        $stub = $this->getMockForAbstractClass('\ONGR\ElasticsearchBundle\Document\AbstractDocument');
        $stub->__set('highlight', null);
        $stub->getHighLight();
    }
}
