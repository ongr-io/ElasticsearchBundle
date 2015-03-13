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

use ONGR\ElasticsearchBundle\Document\AbstractDocument;

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
        $document = new AbstractDocument();
        $document->__set('_id', '123');
        $document->__set('_score', '123');
        $document->__set('_ttl', '123');
        $document->__set('_parent', '123');
        $document->__set('foo', '123');

        $this->assertEquals('123', $document->__get('_id'));
        $this->assertEquals('123', $document->__get('_score'));
        $this->assertEquals('123', $document->__get('_ttl'));
        $this->assertEquals('123', $document->__get('_parent'));
        $this->assertEquals(null, $document->__get('foo'));
        $this->assertEquals('123', $document->getScore());
        $this->assertTrue($document->hasParent());
    }

    /**
     * Tests if getHighlight() throws Exception.
     *
     * @expectedException        \UnderflowException
     * @expectedExceptionMessage Highlight not set.
     */
    public function testGetHighlightException()
    {
        $document = new AbstractDocument();
        $document->__set('highlight', null);
        $document->getHighLight();
    }
}
