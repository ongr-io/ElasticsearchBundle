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

use ONGR\ElasticsearchBundle\DSL\Aggregation\RangeAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TopHitsAggregation;
use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\DSL\Sort\Sort;
use ONGR\ElasticsearchBundle\DSL\Sort\Sorts;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SubAggregationTest extends AbstractElasticsearchTestCase
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
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'surface' => 'weak',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'surface' => 'weak',
                        'price' => 15.1,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return Search
     */
    public function getTestSubAggregationsData()
    {
        $out = [];

        // Case 0: top hits aggregation inside ranges.
        $search = new Search();

        $rangeAgg = new RangeAggregation('range');
        $rangeAgg->setField('price');
        $rangeAgg->addRange(null, 15);
        $rangeAgg->addRange(15);

        $topHitsAgg = new TopHitsAggregation('top_hits');
        $topHitsAgg->setSort(
            new Sorts(
                new Sort(
                    'price',
                    Sort::ORDER_DESC
                )
            )
        );

        $rangeAgg->addAggregation($topHitsAgg);

        $search->addAggregation($rangeAgg);

        $expectedTopHits = [
            [1],
            [2, 3],
        ];

        $out[] = [$search, $expectedTopHits];

        return $out;
    }

    /**
     * Test for terms aggregation.
     *
     * @param Search $search
     * @param array  $expectedTopHits
     *
     * @dataProvider getTestSubAggregationsData
     */
    public function testSubAggregations($search, $expectedTopHits)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $rangeAggs = $repo
            ->execute($search)
            ->getAggregations()
            ->find('range');

        foreach ($rangeAggs as $bucket => $singleRange) {
            $ids = [];

            foreach ($singleRange->find('top_hits') as $document) {
                $ids[] = $document->getId();
            }

            $this->assertEquals(
                $expectedTopHits[$bucket],
                $ids
            );
        }
    }
}
