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

use ONGR\ElasticsearchBundle\Mapping\Caser;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;

class ConverterTest extends TestCase
{
    use SetUpTearDownTrait;

    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @inheritdoc
     */
    public function doSetUp()
    {
        $this->metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->converter = new Converter($this->metadataCollector);
    }

    public function dataProviderForAssignArrayToObjectWhenDateIsObject()
    {
        return [
            # Case 0.
            [
                [
                    'title' => 'Foo',
                ],
            ],
            # Case 1.
            [
                [
                    'title' => 'Boo',
                    'released' => new \DateTime(),
                ],
            ],
            # Case 2.
            [
                [
                    'title' => 'Bar',
                    'released' => null,
                ]
            ],
            # Case 3.
            [
                [
                    'limited' => null,
                ],
                [
                    'limited' => false,
                ]
            ],
            # Case 4.
            [
                [
                    'limited' => 1,
                ],
                [
                    'limited' => true,
                ]
            ],
            # Case 5.
            [
                [
                    'limited' => true,
                ],
                [
                    'limited' => true,
                ]
            ]
        ];
    }

    /**
     * Tests array conversion to the object.
     *
     * @param array $product
     * @param array $expected
     *
     * @dataProvider dataProviderForAssignArrayToObjectWhenDateIsObject
     */
    public function testAssignArrayToObjectWhenDateIsObject($product, $expected = [])
    {
        $aliases = [
            'title' => [
                'propertyName' => 'title',
                'type' => 'text',
                'hashmap' => false,
                'methods' => [
                    'getter' => 'getTitle',
                    'setter' => 'setTitle',
                ],
                'propertyType' => 'private',
            ],
            'released' => [
                'propertyName' => 'released',
                'type' => 'datetime',
                'hashmap' => false,
                'methods' => [
                    'getter' => 'getReleased',
                    'setter' => 'setReleased',
                ],
                'propertyType' => 'private',

            ],
            'limited' => [
                'propertyName' => 'limited',
                'type' => 'boolean',
                'hashmap' => false,
                'methods' => [
                    'getter' => 'getLimited',
                    'setter' => 'setLimited',
                ],
                'propertyType' => 'private',

            ]
        ];
        /** @var Product $productDocument */
        $productDocument = $this->converter->assignArrayToObject($product, new Product(), $aliases);

        foreach (array_keys($product) as $key) {
            $expect = isset($expected[$key]) ? $expected[$key] : $product[$key];
            $this->assertEquals($expect, $productDocument->{'get'.Caser::snake($key)}());
        }
    }
}
