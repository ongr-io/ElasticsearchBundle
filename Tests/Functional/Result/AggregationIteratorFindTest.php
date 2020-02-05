<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Result;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\RangeAggregation;

class AggregationIteratorFindTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'Foo',
                    'number' => 10.45,
                ],
                [
                    '_id' => 2,
                    'title' => 'Bar',
                    'number' => 32,
                ],
                [
                    '_id' => 3,
                    'title' => 'Acme',
                    'number' => 15.1,
                ],
            ],
        ];
    }

    /**
     * Aggregation iterator main test.
     */
    public function testIteration()
    {
        $expected = [
            [
                'key' => '*-20.0',
                'doc_count' => 2,
            ],
            [
                'key' => '20.0-*',
                'doc_count' => 1,
            ],
        ];

        $index = $this->getIndex(DummyDocument::class);

        $rangeAggregation = new RangeAggregation('range', 'number');
        $rangeAggregation->addRange(null, 20);
        $rangeAggregation->addRange(20, null);

        $search = $index
            ->createSearch()
            ->addAggregation($rangeAggregation);

        $results = $index->findDocuments($search);
        $rangeResult = $results->getAggregation('range');

        $this->assertInstanceOf(AggregationValue::class, $rangeResult);

        foreach ($rangeResult->getBuckets() as $aggKey => $subAgg) {
            $this->assertInstanceOf(AggregationValue::class, $subAgg);
            $this->assertEquals($expected[$aggKey]['key'], $subAgg->getValue('key'));
            $this->assertEquals($expected[$aggKey]['doc_count'], $subAgg->getValue('doc_count'));
        }
    }
}
