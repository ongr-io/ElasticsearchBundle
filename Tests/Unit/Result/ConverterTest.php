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

    public function setUp()
    {

    }

    /**
     * Tests if ConvertToDocument() throws Exception.
     *
     * @expectedException        \LogicException
     * @expectedExceptionMessage Got document of unknown type 'foo'.
     */
    public function testConvertToDocumentException()
    {
        $converter = new Converter();
        $converter->convertToDocument(['_type' => 'foo'], null);
    }

    /**
     * Tests assignArrayToObject().
     */
    public function testAssignArrayToObject()
    {
        $stub = $this
            ->getMockBuilder('\ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\ProductDocument')
            ->getMock();

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

        $converter = new Converter(null);

        $converter->assignArrayToObject(
            [
                'foo' => 'bar',
                'price' => (float)123,
            ],
            $stub,
            [
                'price' => [
                    'propertyName' => 'price',
                    'type' => 'float',
                ],
            ]
        );
    }
}
