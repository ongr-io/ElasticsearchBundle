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

use ONGR\ElasticsearchBundle\DSL\Aggregation\FiltersAggregation;
use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Filter\TermFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class FiltersAggregationTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.4.0', '<'],
        ];
    }

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
                        'title' => 'bar',
                        'price' => 32,
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
     * Test for not anonymous filters.
     */
    public function testNotAnonymousFiltersAggregation()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new FiltersAggregation('test_agg');
        $aggregation->addFilter(new TermFilter('title', 'bar'), 'test');
        $search = $repo->createSearch()->addAggregation($aggregation);

        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertEquals(1, $results['aggregations']['agg_test_agg']['buckets']['test']['doc_count']);
    }

    /**
     * Test for anonymous filters.
     */
    public function testAnonymousFiltersAggregation()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new FiltersAggregation('test_agg');
        $aggregation->setAnonymous(true);
        $aggregation->addFilter(new TermFilter('title', 'bar'));
        $search = $repo->createSearch()->addAggregation($aggregation);

        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertEquals(1, $results['aggregations']['agg_test_agg']['buckets'][0]['doc_count']);
    }
}
