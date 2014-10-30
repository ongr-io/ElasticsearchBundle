<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;

class MetadataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns product fixture mapping.
     *
     * @return array
     */
    protected function getProductMapping()
    {
        return [
            'title' => [
                'type' => 'string',
                'fields' => [
                    'raw' => ['type' => 'string'],
                ],
            ],
            'description' => [
                'type' => 'string',
            ],
            'price' => [
                'type' => 'float',
            ],
            'location' => [
                'type' => 'geo_point',
            ],
            'url' => [
                'type' => 'object',
                'properties' => [
                    'url' => [
                        'type' => 'string',
                    ],
                    'key' => [
                        'type' => 'string',
                        'index' => 'no',
                    ],
                    'cdn' => [
                        'type' => 'object',
                        'properties' => [
                            'cdn_url' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            'images' => [
                'type' => 'nested',
                'properties' => [
                    'url' => [
                        'type' => 'string',
                    ],
                    'title' => [
                        'type' => 'string',
                        'index' => 'no',
                    ],
                    'description' => [
                        'type' => 'string',
                        'index' => 'no',
                    ],
                    'cdn' => [
                        'type' => 'object',
                        'properties' => [
                            'cdn_url' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests if mappings are being cached by bundle.
     */
    public function testGetMappingCache()
    {
        $mapping = ['foo' => [
            'properties' => ['bar' => 'baz'],
            'fields' => [
                '_parent' => null,
                '_ttl' => null,
            ],
        ]];

        $expectedMapping = ['foo' => ['properties' => ['bar' => 'baz']]];

        /** @var MetadataCollector|\PHPUnit_Framework_MockObject_MockObject $collector */
        $collector = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->setConstructorArgs(
                [
                    [
                        'AcmeDemoBundle' => 'Acme/DemoBundle/AcmeDemoBundle'
                    ],
                    $this->getCachedReaderMock(),
                ]
            )
            ->setMethods(['getBundleMapping'])
            ->getMock();

        $collector
            ->expects($this->exactly(1))
            ->method('getBundleMapping')
            ->with('AcmeDemoBundle')
            ->will($this->returnValue($mapping));

        // Caches.
        $this->assertEquals($expectedMapping, $collector->getMapping('AcmeDemoBundle'));
        // Loads from local cache.
        $this->assertEquals($expectedMapping, $collector->getMapping('AcmeDemoBundle'));
    }

    /**
     * Tests if correct mapping is being returned from bundle.
     *
     * Uses simple non-caching reader.
     */
    public function testGet()
    {
        $collector = new MetadataCollector(
            [
                'AcmeTestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\AcmeTestBundle',
            ],
            new AnnotationReader()
        );

        $mapping = $collector->getMapping('AcmeTestBundle');

        $this->assertEquals(
            [
                'product' => [
                    'properties' => $this->getProductMapping(),
                ],
                'foocontent' => [
                    'properties' => [
                        'header' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'comment' => [
                    '_parent' => [
                        'type' => 'foocontent',
                    ],
                    '_ttl' => [
                        'enabled' => true,
                        'default' => '1d',
                    ],
                    'properties' => [
                        'userName' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            $mapping
        );
    }

    /**
     * Tests if exception is thrown when bundle is not found.
     *
     * @expectedException \LogicException
     */
    public function testBundleNotFound()
    {
        $collector = new MetadataCollector([], $this->getCachedReaderMock());
        $collector->getMapping('AcmeDemoBundle');
    }

    /**
     * @return array
     */
    public function getTestGetMappingByNamespaceData()
    {
        $mapping = [
            'product' => [
                'properties' => $this->getProductMapping(),
                'class' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product',
            ]
        ];

        return [
            ['AcmeTestBundle:Product', $mapping],
            ['ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product', $mapping]
        ];
    }

    /**
     * Tests if correct mapping is retrieved from getMappingByNamespace method.
     *
     * @param string $namespace
     * @param string $expectedMapping
     *
     * @dataProvider getTestGetMappingByNamespaceData
     */
    public function testGetMappingByNamespace($namespace, $expectedMapping)
    {
        $collector = new MetadataCollector(
            [
                'AcmeTestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\AcmeTestBundle',
            ],
            new AnnotationReader()
        );

        $mapping = $collector->getMappingByNamespace($namespace);
        $this->assertEquals($expectedMapping['product']['properties'], $mapping['product']['properties']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileCacheReader
     */
    protected function getCachedReaderMock()
    {
        return $this
            ->getMockBuilder('Doctrine\Common\Annotations\FileCacheReader')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
