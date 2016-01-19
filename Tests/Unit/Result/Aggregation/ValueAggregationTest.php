<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result\Aggregation;

use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;

class ValueAggregationTest extends \PHPUnit_Framework_TestCase
{
    private function getSampleResponse()
    {
        return [
            'doc_count_error_upper_bound' => 0,
            'sum_other_doc_count' => 52,
            'buckets' => [
                [
                    'key' => 'Terre Cortesi Moncaro',
                    'doc_count' => 7,
                    'agg_avg_price' => [
                        'value' => 20.42714282444545,
                    ],
                ],
                [
                    'key' => 'Casella Wines',
                    'doc_count' => 4,
                    'agg_avg_price' => [
                        'value' => 10.47249972820282,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for getValue().
     */
    public function testGetValue()
    {
        $agg = new ValueAggregation($this->getSampleResponse());

        $this->assertEquals(52, $agg->getValue('sum_other_doc_count'));
        $this->assertNull($agg->getValue('fake_non_set_key'));
    }

    /**
     * Test for getBuckets().
     */
    public function testGetBuckets()
    {
        $agg = new ValueAggregation($this->getSampleResponse());
        $buckets = $agg->getBuckets();

        $this->assertCount(2, $buckets);

        foreach ($buckets as $bucket) {
            $this->assertInstanceOf('\ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation', $bucket);
        }
    }

    /**
     * Test for getBuckets() in case none set.
     */
    public function testGetBucketsNotSet()
    {
        $agg = new ValueAggregation([]);
        $this->assertNull($agg->getBuckets());
    }

    /**
     * Test for getAggregation().
     */
    public function testGetAggregation()
    {
        $agg = new ValueAggregation($this->getSampleResponse()['buckets'][0]);

        $this->assertInstanceOf(
            '\ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation',
            $agg->getAggregation('avg_price')
        );
    }

    /**
     * Test for getAggregation() in case none set.
     */
    public function testGetAggregationNotSet()
    {
        $agg = new ValueAggregation([]);
        $this->assertNull($agg->getAggregation('foo'));
    }

    /**
     * Test for find().
     */
    public function testFind()
    {
        // TODO: test it
    }
}
