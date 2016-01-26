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

use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;
use ONGR\ElasticsearchDSL\Aggregation\RangeAggregation;
use ONGR\ElasticsearchDSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

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
                'key' => 'weak',
                'doc_count' => 2,
            ],
            [
                'key' => 'solid',
                'doc_count' => 1,
            ],
        ];

        $repository = $this
            ->getManager()
            ->getRepository('AcmeBarBundle:Product');
        $search = $repository
            ->createSearch()
            ->addAggregation($this->buildAggregation());
        $results = $repository->execute($search);
        $agg = $results->getAggregation('terms');

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue', $agg);

        foreach ($agg->getBuckets() as $aggKey => $subAgg) {
            $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue', $subAgg);
            $this->assertEquals($expected[$aggKey]['key'], $subAgg->getValue('key'));
            $this->assertEquals($expected[$aggKey]['doc_count'], $subAgg->getValue('doc_count'));
        }
    }

    /**
     * Get aggregation collection with several aggregations registered.
     *
     * @return AbstractAggregation
     */
    private function buildAggregation()
    {
        $aggregation = new TermsAggregation('terms');
        $aggregation->setField('description');
        $aggregation2 = new RangeAggregation('range');
        $aggregation2->setField('price');
        $aggregation2->addRange(null, 20);
        $aggregation2->addRange(20, null);
        $aggregation->addAggregation($aggregation2);

        return $aggregation;
    }
}
