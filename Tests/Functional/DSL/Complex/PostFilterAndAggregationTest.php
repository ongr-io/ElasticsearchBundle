<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Complex;

use ONGR\ElasticsearchBundle\DSL\Aggregation\FilterAggregation;
use ONGR\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use ONGR\ElasticsearchBundle\DSL\Filter\RangeFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class PostFilterAndAggregationTest extends ElasticsearchTestCase
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
                        'title' => 'baz',
                        'price' => 144,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 256,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 260,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'foo',
                        'price' => 275,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 5,
                        'title' => 'foo',
                        'price' => 276,
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 6,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'Lorem ipsum',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test filters in aggregations and post filter.
     */
    public function testBoolWithFuzzyQueryAndSortFilter()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch();

        $rangeFilter = new RangeFilter('price', ['from' => 200, 'to' => 999]);
        $search->addPostFilter($rangeFilter);

        $name = 'foo';
        $TermsAgg = new TermsAggregation($name);
        $TermsAgg->setField('title');
        $TermsAgg->addParameter('include', $name);

        $filterAgg = new FilterAggregation($name . '-filter');

        $filters = $search->getPostFilters();
        $filterAgg->setFilter($filters);

        $filterAgg->addAggregation($TermsAgg);

        $search->addAggregation($filterAgg);

        $repo->execute($search, Repository::RESULTS_RAW);
    }
}
