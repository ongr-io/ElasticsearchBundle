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

use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Result\Suggestion\OptionIterator;
use ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionEntry;

class DocumentIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testIteration().
     *
     * @return array
     */
    public function getTestIterationData()
    {
        $cases = [];

        // Case #0 Standard iterator.
        $cases[] = [
            [
                'hits' => [
                    'total' => 1,
                    'hits' => [
                        [
                            '_type' => 'content',
                            '_id' => 'foo',
                            '_score' => 0,
                            '_source' => ['header' => 'Test header'],
                        ],
                    ],
                ],
            ],
        ];

        // Case #1 Iterating when document has only selected fields.
        $cases[] = [
            [
                'hits' => [
                    'total' => 1,
                    'hits' => [
                        [
                            '_type' => 'content',
                            '_id' => 'foo',
                            '_score' => 0,
                            'fields' => [
                                'header' => ['Test header'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $cases;
    }

    /**
     * Iteration test.
     *
     * @param array $rawData
     *
     * @dataProvider getTestIterationData()
     */
    public function testIteration($rawData)
    {
        $iterator = new DocumentIterator($rawData, $this->getTypesMapping(), $this->getBundleMapping());
        $this->assertContentEquals($iterator, ['Test header']);
    }

    /**
     * Check if offset set works as expected.
     */
    public function testOffsetSet()
    {
        $rawData = [
            'hits' => [
                'total' => 3,
                'hits' => [
                    [
                        '_type' => 'content',
                        '_id' => 'foo',
                        '_score' => 0,
                        '_source' => ['header' => 'Test header'],
                    ],
                    [
                        '_type' => 'content',
                        '_id' => 'foo1',
                        '_score' => 0,
                        '_source' => ['header' => 'Test header2'],
                    ],
                    [
                        '_type' => 'content',
                        '_id' => 'foo2',
                        '_score' => 0,
                        '_source' => ['header' => 'Test header3'],
                    ],
                ],
            ],
        ];

        // Check if inital data is correct.
        $iterator = new DocumentIterator($rawData, $this->getTypesMapping(), $this->getBundleMapping());
        $this->assertContentEquals($iterator, [0 => 'Test header', 1 => 'Test header2', 2 => 'Test header3']);

        // Set a few numeric and string offsets.
        $iterator['testOffset'] = $rawData['hits']['hits'][0];
        $iterator['a'] = $rawData['hits']['hits'][1];
        $iterator[5] = $rawData['hits']['hits'][1];
        $iterator[1] = $rawData['hits']['hits'][2];
        $expectedHeaders = [
            0 => 'Test header',
            1 => 'Test header3',
            2 => 'Test header3',
            5 => 'Test header2',
            'testOffset' => 'Test header',
            'a' => 'Test header2',
        ];
        $this->assertContentEquals($iterator, $expectedHeaders);

        // Check empty offset, should append array with highest int as a key.
        $iterator[] = $rawData['hits']['hits'][0];
        $iterator[] = $rawData['hits']['hits'][1];
        $expectedHeaders = [
            0 => 'Test header',
            1 => 'Test header3',
            2 => 'Test header3',
            5 => 'Test header2',
            6 => 'Test header',
            7 => 'Test header2',
            'testOffset' => 'Test header',
            'a' => 'Test header2',
        ];
        $this->assertContentEquals($iterator, $expectedHeaders);

        // Iterate through again.
        $this->assertContentEquals($iterator, $expectedHeaders);

        // Check what happens when we have only string offsets.
        $iterator = new DocumentIterator([], $this->getTypesMapping(), $this->getBundleMapping());
        $iterator['testOffset'] = $rawData['hits']['hits'][0];
        $iterator['a'] = $rawData['hits']['hits'][1];
        $iterator[] = $rawData['hits']['hits'][2];
        $expectedHeaders = [
            'testOffset' => 'Test header',
            'a' => 'Test header2',
            0 => 'Test header3',
        ];
        $this->assertContentEquals($iterator, $expectedHeaders);

        // Check what happens when we have only string offsets.
        $iterator = new DocumentIterator([], $this->getTypesMapping(), $this->getBundleMapping());
        $iterator[] = $rawData['hits']['hits'][2];
        $expectedHeaders = [
            0 => 'Test header3',
        ];
        $this->assertContentEquals($iterator, $expectedHeaders);
    }

    /**
     * Check if offsetExists works as expected.
     */
    public function testOffsetExists()
    {
        $rawData = [
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_type' => 'content',
                        '_id' => 'foo',
                        '_score' => 0,
                        '_source' => ['header' => 'Test header'],
                    ],
                ],
            ],
        ];

        $iterator = new DocumentIterator($rawData, $this->getTypesMapping(), $this->getBundleMapping());
        $iterator['test'] = $rawData['hits']['hits'][0];
        $iterator[] = $rawData['hits']['hits'][0];

        $this->assertTrue(isset($iterator[0]), 'Item should be set from initial data.');
        $this->assertTrue(isset($iterator['test']), 'String offset should be set');
        $this->assertTrue(isset($iterator[1]), 'Highest array integer index should be set.');

        $this->assertFalse(isset($iterator[2]));
    }

    /**
     * Check if offset unset works as expected.
     */
    public function testOffsetUnset()
    {
        $rawData = [
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_type' => 'content',
                        '_id' => 'foo',
                        '_score' => 0,
                        '_source' => ['header' => 'Test header'],
                    ],
                ],
            ],
        ];

        $iterator = new DocumentIterator($rawData, $this->getTypesMapping(), $this->getBundleMapping());

        $this->assertTrue(isset($iterator[0]), 'Item should be set from initial data.');
        unset($iterator[0]);
        $this->assertFalse(isset($iterator[0]), 'Item should be unset.');
    }

    /**
     * Test for getAggregations().
     */
    public function testGetAggregations()
    {
        $rawData = [
            'hits' => [
                'total' => 0,
                'hits' => [],
            ],
            'aggregations' => [
                'agg_foo' => ['doc_count' => 1],
            ],
        ];

        $iterator = new DocumentIterator($rawData, [], []);

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator',
            $iterator->getAggregations()
        );
        $this->assertEquals(new ValueAggregation(['doc_count' => 1]), $iterator->getAggregations()['foo']);
    }

    /**
     * Test for getSuggestions().
     */
    public function testGetSuggestions()
    {
        $rawData = [
            'hits' => [
                'total' => 0,
                'hits' => [],
            ],
            'suggest' => [
                'foo' => [
                    [
                        'text' => 'foobar',
                        'offset' => 0,
                        'length' => 6,
                        'options' => [
                            [
                                'text' => 'foobar',
                                'freq' => 77,
                                'score' => 0.8888889,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $iterator = new DocumentIterator($rawData, [], []);
        $suggestions = $iterator->getSuggestions();

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionIterator',
            $iterator->getSuggestions()
        );

        $expectedSuggestion = new SuggestionEntry(
            'foobar',
            0,
            6,
            new OptionIterator($rawData['suggest']['foo'][0]['options'])
        );

        $this->assertEquals($expectedSuggestion, $suggestions['foo'][0]);
    }

    /**
     * Check if document iterator contains the documents we expect.
     *
     * @param array|DocumentIterator $iterator
     * @param array                  $expectedHeaders
     */
    private function assertContentEquals($iterator, $expectedHeaders)
    {
        foreach ($iterator as $key => $item) {
            $this->assertInstanceOf(
                'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Content',
                $item
            );
            $this->assertEquals($expectedHeaders[$key], $item->header);
            unset($expectedHeaders[$key]);
        }
        $this->assertEmpty($expectedHeaders);
    }

    /**
     * Returns bundle mapping for testing.
     *
     * @return array
     */
    private function getBundleMapping()
    {
        return [
            'AcmeTestBundle:Content' => $this->getClassMetadata(
                [
                    'aliases' => [
                        'header' => [
                            'propertyName' => 'header',
                            'type' => 'string',
                        ],
                    ],
                    'properties' => [
                        'header' => ['type' => 'string'],
                    ],
                    // Should be generated but in this example will be using original.
                    'proxyNamespace' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Content',
                    'namespace' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Content',
                ]
            ),
        ];
    }

    /**
     * Returns types mapping for testing.
     *
     * @return array
     */
    private function getTypesMapping()
    {
        return ['content' => 'AcmeTestBundle:Content'];
    }

    /**
     * Returns class metadata mock.
     *
     * @param array $options
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    private function getClassMetadata(array $options)
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($options as $name => $value) {
            $mock
                ->expects($this->any())
                ->method('get' . ucfirst($name))
                ->will($this->returnValue($value));
        }

        return $mock;
    }
}
