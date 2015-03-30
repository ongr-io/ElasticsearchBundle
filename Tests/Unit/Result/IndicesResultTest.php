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

use ONGR\ElasticsearchBundle\Result\IndicesResult;

/**
 * Unit tests for IndicesResult.
 */
class IndicesResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * IndicesResult test.
     */
    public function testGetTotalGetSuccessfulGetFailed()
    {
        $indices = [
            '_indices' => [
                'ongr-elasticsearch-bundle-default-test' => [
                    '_shards' => [
                        'total' => 5,
                        'successful' => 5,
                        'failed' => 0,
                    ],
                ],
                'foo' => [
                    '_shards' => [
                        'total' => 5,
                        'successful' => 5,
                        'failed' => 0,
                    ],
                ],
            ],
        ];
        $indicesResult = new IndicesResult($indices);

        $getExpected = [
            'ongr-elasticsearch-bundle-default-test' => 5,
            'foo' => 5,
        ];

        $getExpectedFailed = [
            'ongr-elasticsearch-bundle-default-test' => 0,
            'foo' => 0,
        ];
        $getTotal = $indicesResult->getTotal();
        $getSuccessful = $indicesResult->getSuccessful();
        $getFailed = $indicesResult->getFailed();
        $this->assertEquals($getExpected, $getTotal);
        $this->assertEquals($getExpected, $getSuccessful);
        $this->assertEquals($getExpectedFailed, $getFailed);

        $getExpectedIndex = ['ongr-elasticsearch-bundle-default-test' => 5];
        $getExpectedIndexFailed = ['ongr-elasticsearch-bundle-default-test' => 0];

        $getTotalIndex = $indicesResult->getTotal(['ongr-elasticsearch-bundle-default-test', 'not_exist']);
        $getFailedIndex = $indicesResult->getFailed(['ongr-elasticsearch-bundle-default-test']);
        $this->assertEquals($getExpectedIndex, $getTotalIndex);
        $this->assertEquals($getExpectedIndexFailed, $getFailedIndex);

        $this->assertEquals($indices, $indicesResult->getRaw());
    }
}
