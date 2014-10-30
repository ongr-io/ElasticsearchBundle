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

use ONGR\ElasticsearchBundle\Result\DocumentHighlight;

/**
 * Unit tests for Document highlight.
 */
class DocumentHighlightTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if offset set throws an exception.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method not supported. Read only.
     */
    public function testOffsetSet()
    {
        $highlight = new DocumentHighlight([]);
        $highlight->offsetSet('test', 'test');
    }

    /**
     * Check if offset set throws an exception.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method not supported.
     */
    public function testOffsetUnset()
    {
        $highlight = new DocumentHighlight([]);
        $highlight->offsetUnset('test');
    }
}
