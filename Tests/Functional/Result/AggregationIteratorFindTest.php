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

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\RangeAggregation;

class AggregationIteratorFindTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 1,
                        'title' => 'Onion',
                        'description' => 'solid',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'Tomato',
                        'description' => 'weak',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'Pizza',
                        'description' => 'weak',
                        'price' => 15.1,
                    ],
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

        $repository = $this
            ->getManager()
            ->getRepository('TestBundle:Product');

        $rangeAggregation = new RangeAggregation('range', 'price');
        $rangeAggregation->addRange(null, 20);
        $rangeAggregation->addRange(20, null);

        $search = $repository
            ->createSearch()
            ->addAggregation($rangeAggregation);

        $results = $repository->findDocuments($search);
        $rangeResult = $results->getAggregation('range');

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue', $rangeResult);

        foreach ($rangeResult->getBuckets() as $aggKey => $subAgg) {
            $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue', $subAgg);
            $this->assertEquals($expected[$aggKey]['key'], $subAgg->getValue('key'));
            $this->assertEquals($expected[$aggKey]['doc_count'], $subAgg->getValue('doc_count'));
        }
    }
}
