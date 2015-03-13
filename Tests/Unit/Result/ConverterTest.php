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

use ONGR\ElasticsearchBundle\Result\Converter;

/**
 * Tests result converter.
 *
 * Class ConverterTest
 *
 * @package ONGR\ElasticsearchBundle\Tests\Unit\Result
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if ConvertToDocument() throws Exception.
     *
     * @expectedException        \LogicException
     * @expectedExceptionMessage Got document of unknown type 'foo'.
     */
    public function testConvertToDocumentException()
    {
        $converter = new Converter([], []);
        $converter->convertToDocument(['_type' => 'foo']);
    }

    /**
     * Tests assignArrayToObject().
     */
    public function testAssignArrayToObject()
    {
        $stub = new \StdClass();
        $converter = new Converter([], []);
        $object = $converter->assignArrayToObject(['foo' => 'bar'], $stub, []);
        $this->assertEquals('bar', $object->foo);
    }

    /**
     * Tests if getAlias() throws Exception.
     *
     * @expectedException        \DomainException
     */
    public function testGetAliasException()
    {
        /** @var \ONGR\ElasticsearchBundle\Document\DocumentInterface $stub */
        $stub = $this->getMockBuilder('\ONGR\ElasticsearchBundle\Document\DocumentInterface')->getMock();
        $converter = new Converter([], []);
        $converter->convertToArray($stub);
    }
}
