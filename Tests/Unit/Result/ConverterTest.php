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
use ONGR\ElasticsearchBundle\Result\DocumentHighlight;

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
        $stub = $this
            ->getMockBuilder('\ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Item')
            ->getMock();

        $documentHighlight = new DocumentHighlight([]);

        $stub
            ->expects($this->once())
            ->method('setHighlight')
            ->with($this->identicalTo($documentHighlight));
        $stub
            ->expects($this->once())
            ->method('__set')
            ->with(
                $this->equalTo('foo'),
                $this->equalTo('bar')
            );
        $stub
            ->expects($this->once())
            ->method('setPrice')
            ->with($this->equalTo(123));

        $converter = new Converter([], []);

        $converter->assignArrayToObject(
            [
                'foo' => 'bar',
                'price' => (float)123,
                'highlight' => $documentHighlight,
            ],
            $stub,
            [
                'price' => [
                    'propertyName' => 'price',
                    'type' => 'float',
                ],
                'highlight' => [
                    'propertyName' => 'highlight',
                    'type' => 'DocumentHighlight',
                ],
            ]
        );
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
