<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Aggregation;

use ONGR\ElasticsearchBundle\DSL\Aggregation\MissingAggregation;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class MissingAggregationTest extends AbstractElasticsearchTestCase
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
                        'title' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'pizza',
                        'price' => 15.1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for missing aggregation.
     */
    public function testMissingAggregation()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $agg = new MissingAggregation('missing_prices');
        $agg->setField('price');
        $search = $repo->createSearch()->addAggregation($agg);
        $results = $repo->execute($search, $repo::RESULTS_RAW);

        $expectedResult = [
            'agg_missing_prices' => [
                'doc_count' => 2,
            ],
        ];

        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResult, $results['aggregations']);
    }
}
