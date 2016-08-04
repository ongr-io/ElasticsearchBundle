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
                    'body' => [
                        [
                            'index' => ['_index' => 'test', '_type' => 'product'],
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
                    'body' => [
                        [
                            'index' => ['_index' => 'test', '_type' => 'product', '_id' => 'foo'],
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
                    'body' => [
                        [
                            'create' => ['_index' => 'test', '_type' => 'product'],
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
                    'body' => [
                        [
                            'delete' => ['_index' => 'test', '_type' => 'product', '_id' => 'foo'],
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
                    'body' => [
                        [
                            'update' => ['_index' => 'test', '_type' => 'product'],
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
                    'body' => [
                        [
                            'update' => ['_index' => 'test', '_type' => 'product'],
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
        $bulkResponse = ['errors' => false, 'items' => []];
        $indices = $this->getMock('Elasticsearch\Namespaces\IndicesNamespace', [], [], '', false);

        $esClient = $this->getMock('Elasticsearch\Client', [], [], '', false);
        $esClient->expects($this->once())->method('bulk')->with($expected)->will($this->returnValue($bulkResponse));
        $esClient->expects($this->any())->method('indices')->will($this->returnValue($indices));

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = new Manager('test', [], $esClient, ['index' => 'test'], $metadataCollector, $converter);

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
        $bulkResponse = ['errors' => false, 'items' => []];
        $expected = $this->getTestBulkData()['update_script']['expected'];
        $expected['refresh'] = true;
        $calls = $this->getTestBulkData()['update_script']['calls'];
        $indices = $this->getMock('Elasticsearch\Namespaces\IndicesNamespace', [], [], '', false);

        $esClient = $this->getMock('Elasticsearch\Client', [], [], '', false);
        $esClient->expects($this->any())->method('bulk')->with($expected)->willReturn($bulkResponse);
        $esClient->expects($this->any())->method('indices')->will($this->returnValue($indices));

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = new Manager('test', [], $esClient, ['index' => 'test'], $metadataCollector, $converter);
        $manager->setBulkParams(['refresh' => true]);
        $manager->setCommitMode('flush');

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
        $esClient = $this->getMock('Elasticsearch\Client', ['clearScroll'], [], '', false);
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
            ['index' => 'test'],
            $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
                ->disableOriginalConstructor()
                ->getMock()
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
        $manager = $configuration[0];
        $client = $configuration[1];
        $search = $configuration[2];
        $array = [
            '_source' => [
                [
                    'price' => 1,
                    'title' => 'test title'
                ]
            ]
        ];
        $client->expects($this->any())->method('search')->willReturn($array);
        $result = $manager->execute(['product'], $search, 'array');
        $this->assertEquals($result, $array['_source']);
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
        $array = [
            'hits' => [
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
            ]
        ];
        $expected = [
            ['url', 'title']
        ];
        $client->expects($this->any())->method('search')->willReturn($array);
        $result = $manager->execute(['product'], $search, 'array');
        $this->assertEquals($result, $expected);
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
}
