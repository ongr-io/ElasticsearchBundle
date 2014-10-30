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

use ONGR\ElasticsearchBundle\Result\RawResultIterator;

class RawResultIteratorTest extends \PHPUnit_Framework_TestCase
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
            [
                [
                    '_type' => 'content',
                    '_id' => 'foo',
                    '_score' => 0,
                    '_source' => [
                        'header' => 'Test header',
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
     * @param array $expectedDocuments
     *
     * @dataProvider getTestIterationData()
     */
    public function testIteration($rawData, $expectedDocuments)
    {
        $iterator = new RawResultIterator($rawData);

        $this->assertCount(1, $iterator);

        foreach ($iterator as $key => $document) {
            $this->assertEquals($expectedDocuments[$key], $document);
        }
    }
}
