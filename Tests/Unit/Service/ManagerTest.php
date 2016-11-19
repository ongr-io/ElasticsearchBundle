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

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Search;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testBulk()
     *
     * @return array[]
     */
    public function getTestBulkData()
    {
        return [
            'index_document' => [
                'expected' => [
                    'index' => [
                        '_index' => 'test',
                    ],
                    'body' => [
                        [
                            'index' => ['_type' => 'product'],
                        ],
                        [
                            'field1' => 'value1',
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'index',
                        'product',
                        [
                            'field1' => 'value1'
                        ],
                    ],
                ],
            ],
            'index_document_with_id' => [
                'expected' => [
                    'index' => [
                        '_index' => 'test',
                    ],
                    'body' => [
                        [
                            'index' => ['_type' => 'product', '_id' => 'foo'],
                        ],
                        [
                            'field1' => 'value1',
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'index',
                        'product',
                        [
                            '_id' => 'foo',
                            'field1' => 'value1'
                        ],
                    ],
                ],
            ],
            'create_document' => [
                'expected' => [
                    'index' => [
                        '_index' => 'test',
                    ],
                    'body' => [
                        [
                            'create' => ['_type' => 'product'],
                        ],
                        [
                            'field1' => 'value1',
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'create',
                        'product',
                        [
                            'field1' => 'value1'
                        ],
                    ],
                ],
            ],
            'delete_document' => [
                'expected' => [
                    'index' => [
                        '_index' => 'test',
                    ],
                    'body' => [
                        [
                            'delete' => ['_type' => 'product', '_id' => 'foo'],
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'delete',
                        'product',
                        [
                            '_id' => 'foo',
                        ],
                    ],
                ],
            ],
            'update_doc' => [
                'expected' => [
                    'index' => [
                        '_index' => 'test',
                    ],
                    'body' => [
                        [
                            'update' => ['_type' => 'product'],
                        ],
                        [
                            'doc' => ['title' => 'Sample'],
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'update',
                        'product',
                        [
                            'doc' => [
                                'title' => 'Sample',
                            ],
                        ],
                    ],
                ],
            ],
            'update_script' => [
                'expected' => [
                    'index' => [
                        '_index' => 'test',
                    ],
                    'body' => [
                        [
                            'update' => ['_type' => 'product'],
                        ],
                        [
                            'script' => 'ctx._source.counter += count',
                            'params' => ['count' => '4'],
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'update',
                        'product',
                        [
                            'script' => 'ctx._source.counter += count',
                            'params' => ['count' => '4'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test if manager builds correct bulk structure
     *
     * @param array  $expected
     * @param array  $calls
     *
     * @dataProvider getTestBulkData()
     */
    public function testBulk($expected, $calls)
    {
        $indices = $this->createMock('Elasticsearch\Namespaces\IndicesNamespace');

        $esClient = $this->createMock('Elasticsearch\Client');
        $esClient->expects($this->once())->method('bulk')->with($expected);
        $esClient->expects($this->any())->method('indices')->will($this->returnValue($indices));

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = new Manager('test', [], $esClient, ['index' => 'test'], $metadataCollector, $converter);
        $manager->setEventDispatcher($dispatcher);

        foreach ($calls as list($operation, $type, $query)) {
            $manager->bulk($operation, $type, $query);
        }

        $manager->commit();
    }

    /**
     * Test if commits correctly with parameters set to it
     */
    public function testBulkWithCommitModeSet()
    {
        $expected = $this->getTestBulkData()['update_script']['expected'];
        $expected['refresh'] = true;
        $calls = $this->getTestBulkData()['update_script']['calls'];
        $indices = $this->createMock('Elasticsearch\Namespaces\IndicesNamespace');

        $esClient = $this->createMock('Elasticsearch\Client');
        $esClient->expects($this->any())->method('bulk')->with($expected)->willReturn(['errors' => false]);
        $esClient->expects($this->any())->method('indices')->will($this->returnValue($indices));

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        
        $manager = new Manager('test', [], $esClient, ['index' => 'test'], $metadataCollector, $converter);
        $manager->setBulkParams(['refresh' => true]);
        $manager->setCommitMode('flush');
        $manager->setEventDispatcher($dispatcher);

        foreach ($calls as list($operation, $type, $query)) {
            $manager->bulk($operation, $type, $query);
        }

        $this->assertNotNull($manager->commit());

        $manager->setCommitMode('refresh');

        foreach ($calls as list($operation, $type, $query)) {
            $manager->bulk($operation, $type, $query);
        }

        $this->assertNotNull($manager->commit());
    }

    /**
     * Test for clearScroll().
     */
    public function testClearScroll()
    {
        $esClient = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->setMethods(['clearScroll'])
            ->disableOriginalConstructor()
            ->getMock();
        $esClient->expects($this->once())->method('clearScroll')->with(['scroll_id' => 'foo']);

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = new Manager('test', [], $esClient, ['index' => 'test'], $metadataCollector, $converter);
        $manager->clearScroll('foo');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The commit method must be either refresh, flush or none.
     */
    public function testSetCommitModeException()
    {
        /** @var Manager $manager */
        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $manager->setCommitMode('foo');
    }

    /**
     * Returns configured manager, client mock and search object
     *
     * @return array
     */
    public function getPreparedConfiguration()
    {
        $search = new Search();
        $search->addQuery(new MatchAllQuery());
        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $manager = new Manager(
            'test',
            [],
            $client,
            [
                'index' => 'test',
                'body' => [
                    'mappings' => [
                        'foo' => 'bar'
                    ]
                ]
            ],
            $this->createMock('ONGR\ElasticsearchBundle\Mapping\MetadataCollector'),
            $this->createMock('ONGR\ElasticsearchBundle\Result\Converter')
        );

        return [$manager, $client, $search];
    }

    /**
     * Tests convertToNormalizedArray method with different
     * results
     */
    public function testConvertToNormalizedArrayWhenSourceIsSet()
    {
        $configuration = $this->getPreparedConfiguration();
        /** @var Manager $manager */
        $manager = $configuration[0];
        $client = $configuration[1];
        $search = $configuration[2];
        $dummyData = [
            '_id' => '15',
            '_source' => [
                [
                    'price' => 1,
                    'title' => 'test title'
                ]
            ]
        ];
        $client->expects($this->any())->method('search')->willReturn($dummyData);
        $result = $manager->search(['product'], $search->toArray());
        $this->assertEquals($dummyData, $result);
    }

    /**
     * Tests convertToNormalizedArray method with different
     * results
     */
    public function testConvertToNormalizedArrayWhenFieldsAreSet()
    {
        $configuration = $this->getPreparedConfiguration();
        $manager = $configuration[0];
        $client = $configuration[1];
        $search = $configuration[2];
        $dummyData['hits'] = [
            'hits' => [
                [
                    "fields" => [
                        [
                            'name' => 'url',
                            'value' => 'http://ongr.io'
                        ],
                        [
                            'name' => 'title',
                            'value' => 'Elasticsearch test'
                        ]
                    ]
                ]
            ]
        ];
        $client->expects($this->any())->method('search')->willReturn($dummyData);
        $result = $manager->search(['product'], $search->toArray());
        $this->assertEquals($dummyData, $result);
    }

    /**
     * Tests getters and setters
     */
    public function testBulkCommitSizeGettersAndSetters()
    {
        $configuration = $this->getPreparedConfiguration();
        $manager = $configuration[0];
        $manager->setBulkCommitSize(5);
        $this->assertEquals(5, $manager->getBulkCommitSize());
    }

    /**
     * Tests bulk error when invalid operation is provided
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBulkException()
    {
        $manager = $this->getPreparedConfiguration()[0];
        $manager->bulk('not_an_operation', 'product', []);
    }

    /**
     * Tests if getIndexMappings method returns correct results
     */
    public function testGetIndexMappings()
    {
        $manager = $this->getPreparedConfiguration()[0];
        $this->assertEquals(['foo' => 'bar'], $manager->getIndexMappings());
    }
}
