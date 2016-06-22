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

use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue;

class AggregationValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns sample aggregations response.
     *
     * @return array
     */
    private function getSampleResponse()
    {
        return [
            'doc_count_error_upper_bound' => 0,
            'sum_other_doc_count' => 52,
            'buckets' => [
                [
                    'key' => 'Terre Cortesi Moncaro',
                    'doc_count' => 7,
                    'avg_price' => [
                        'value' => 20.42714282444545,
                    ],
                ],
                [
                    'key' => 'Casella Wines',
                    'doc_count' => 4,
                    'avg_price' => [
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
        $agg = new AggregationValue($this->getSampleResponse());

        $this->assertEquals(52, $agg->getValue('sum_other_doc_count'));
        $this->assertNull($agg->getValue('fake_non_set_key'));
    }

    /**
     * Test for getCount()
     */
    public function testGetCount()
    {
        $agg = new AggregationValue($this->getSampleResponse());
        $i = 0;
        foreach ($agg->getBuckets() as $bucket) {
            $this->assertEquals(
                $this->getSampleResponse()['buckets'][$i++]['doc_count'],
                $bucket->getCount()
            );
        }
    }

    /**
     * Test for getBuckets().
     */
    public function testGetBuckets()
    {
        $agg = new AggregationValue($this->getSampleResponse());
        $buckets = $agg->getBuckets();

        $this->assertCount(2, $buckets);

        foreach ($buckets as $bucket) {
            $this->assertInstanceOf('\ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue', $bucket);
        }
    }

    /**
     * Test for getBuckets() in case none set.
     */
    public function testGetBucketsNotSet()
    {
        $agg = new AggregationValue([]);
        $this->assertNull($agg->getBuckets());
    }

    /**
     * Test for getAggregation().
     */
    public function testGetAggregation()
    {
        $agg = new AggregationValue($this->getSampleResponse()['buckets'][0]);

        $this->assertInstanceOf(
            '\ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue',
            $agg->getAggregation('avg_price')
        );
    }

    /**
     * Test for getAggregation() in case none set.
     */
    public function testGetAggregationNotSet()
    {
        $agg = new AggregationValue([]);
        $this->assertNull($agg->getAggregation('foo'));
    }

    /**
     * Test for find().
     */
    public function testFind()
    {
        $responseData = [
            'key' => 'Casa Vinicola Botter Carlo & C. Spa',
            'doc_count' => 3,
            'filter_price' => [
                'doc_count' => 2,
                'avg_price' => [
                    'value' => 8.4899,
                ],
            ],
        ];

        $agg = new AggregationValue($responseData);

        $this->assertNotNull($agg->find('filter_price'));
        $this->assertEquals($agg->getAggregation('filter_price'), $agg->find('filter_price'));

        $this->assertNotNull($agg->find('filter_price.avg_price'));
        $this->assertEquals(
            $agg->getAggregation('filter_price')->getAggregation('avg_price'),
            $agg->find('filter_price.avg_price')
        );
    }

    /**
     * Test for array access interface implementation.
     */
    public function testArrayAccess()
    {
        $agg = new AggregationValue($this->getSampleResponse());

        $this->assertTrue(isset($agg['sum_other_doc_count']));
        $this->assertFalse(isset($agg['fake_key']));

        $this->assertEquals(52, $agg['sum_other_doc_count']);
        $this->assertNull($agg['fake_key']);
    }

    /**
     * Test if exception is thrown when trying to set value using array syntax.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage can not be changed on runtime
     */
    public function testOffsetSetException()
    {
        $agg = new AggregationValue([]);
        $agg['foo'] = 'bar';
    }

    /**
     * Test if exception is thrown when trying to unset value using array syntax.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage can not be changed on runtime
     */
    public function testOffsetUnsetException()
    {
        $agg = new AggregationValue([]);
        unset($agg['foo']);
    }

    /**
     * Test for getIterator().
     */
    public function testGetIterator()
    {
        $agg = new AggregationValue($this->getSampleResponse());
        $buckets = [];

        foreach ($agg as $bucket) {
            $buckets[] = $bucket;
            $this->assertInstanceOf('\ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue', $bucket);
        }

        $this->assertCount(2, $buckets);
    }

    /**
     * Test for getIterator() in case no buckets set.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Can not iterate over aggregation without buckets
     */
    public function testGetIteratorException()
    {
        $agg = new AggregationValue([]);
        foreach ($agg as $bucket) {
            // Just try to iterate
        }
    }
}
