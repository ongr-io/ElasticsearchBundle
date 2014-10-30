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
                            '_source' => [
                                'header' => 'Test header',
                            ],
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
        $typesMapping = [
            'content' => 'AcmeTestBundle:Content',
        ];

        $bundleMapping = [
            'AcmeTestBundle:Content' => [
                'setters' => [
                    'header' => [
                        'exec' => false,
                        'name' => 'header',
                    ]
                ],
                'namespace' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Content',
            ],
        ];

        $iterator = new DocumentIterator($rawData, $typesMapping, $bundleMapping);

        $this->assertCount(1, $iterator);
        $document = $iterator->current();

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Content',
            $document
        );
        $this->assertEquals('Test header', $document->header);
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
                'agg_foo' => [
                    'doc_count' => 1,
                ],
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
        ];

        $iterator = new DocumentIterator($rawData, [], []);

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Result\Suggestions',
            $iterator->getSuggestions()
        );
        $this->assertEquals($rawData['suggest']['foo'], $iterator->getSuggestions()['foo']);
    }
}
