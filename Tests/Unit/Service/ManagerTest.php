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
        $indices = $this->getMock('Elasticsearch\Namespaces\IndicesNamespace', [], [], '', false);

        $esClient = $this->getMock('Elasticsearch\Client', [], [], '', false);
        $esClient->expects($this->once())->method('bulk')->with($expected);
        $esClient->expects($this->any())->method('indices')->will($this->returnValue($indices));

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $config = ['readonly' => false];

        $manager = new Manager('test', $config, $esClient, ['index' => 'test'], $metadataCollector, $converter);

        foreach ($calls as list($operation, $type, $query)) {
            $manager->bulk($operation, $type, $query);
        }

        $manager->commit();
    }

    /**
     * Test for updateMapping() in case mapping was not found.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Mapping for type "test" was not found.
     */
    public function testUpdateMappingException()
    {
        $esClient = $this->getMock('Elasticsearch\Client', [], [], '', false);

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->setMethods(['getClientMapping'])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataCollector->expects($this->once())
            ->method('getClientMapping')
            ->with(['test'])
            ->will($this->returnValue(null));

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $config = ['readonly' => false];

        $manager = new Manager('test', $config, $esClient, ['index' => 'test'], $metadataCollector, $converter);
        $manager->updateMapping(['test']);
    }

    /**
     * Test for clearScroll().
     */
    public function testClearScroll()
    {
        $expectedBody = [
            'scroll_id' => 'foo',
            'client' => [
                'ignore' => 404,
            ],
        ];

        $esClient = $this->getMock('Elasticsearch\Client', ['clearScroll'], [], '', false);
        $esClient->expects($this->once())->method('clearScroll')->with($expectedBody);

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $config = ['readonly' => false];

        $manager = new Manager('test', $config, $esClient, ['index' => 'test'], $metadataCollector, $converter);
        $manager->clearScroll('foo');
    }
}
