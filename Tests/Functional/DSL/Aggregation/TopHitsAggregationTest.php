<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DSL\Aggregation;

use Ongr\ElasticsearchBundle\DSL\Aggregation\AbstractAggregation;
use Ongr\ElasticsearchBundle\DSL\Aggregation\TermsAggregation;
use Ongr\ElasticsearchBundle\DSL\Aggregation\TopHitsAggregation;
use Ongr\ElasticsearchBundle\DSL\Query\FunctionScoreQuery;
use Ongr\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use Ongr\ElasticsearchBundle\DSL\Sort\Sort;
use Ongr\ElasticsearchBundle\DSL\Sort\Sorts;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Function test for top hits aggregation.
 */
class TopHitsAggregationTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            ['1.3.0', '<'],
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
                        'description' => 'solid',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => 'weak',
                        'price' => 15.1,
                    ],
                    [
                        '_id' => 3,
                        'description' => 'weak',
                        'title' => 'pizza',
                        'price' => 16.1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testTopHitsAggregation.
     *
     * @return array
     */
    public function getTopHitsAggregationData()
    {
        $out = [];
        // Case #0 simple top hits aggregation with default values.
        $aggregation = new TopHitsAggregation('test-top_hits');
        $expectedHits = [3, 2, 1];
        $out[] = [
            'aggregation' => $aggregation,
            'expectedHits' => $expectedHits,
        ];

        // Case #1 top hits aggregation with sort.
        $sorts = new Sorts();
        $sorts->addSort(new Sort('price', Sort::ORDER_ASC));
        $sorts->addSort(new Sort('title', Sort::ORDER_DESC));
        $aggregation = new TopHitsAggregation('test-top_hits', null, null, $sorts);
        $expectedHits = [1, 2, 3];
        $out[] = [
            'aggregation' => $aggregation,
            'expectedHits' => $expectedHits,
        ];

        // Case #2 top hits aggregation with from.
        $aggregation = new TopHitsAggregation('test-top_hits', null, 2, null);
        $expectedHits = [1];
        $out[] = [
            'aggregation' => $aggregation,
            'expectedHits' => $expectedHits,
        ];

        // Case #3 top hits aggregation with size.
        $aggregation = new TopHitsAggregation('test-top_hits', 2);
        $expectedHits = [3, 2];
        $out[] = [
            'aggregation' => $aggregation,
            'expectedHits' => $expectedHits,
        ];

        return $out;
    }

    /**
     * Test if aggregation gives the expected results.
     *
     * @param AbstractAggregation $aggregation
     * @param array               $expectedHits
     *
     * @dataProvider getTopHitsAggregationData()
     */
    public function testTopHitsAggregation(AbstractAggregation $aggregation, $expectedHits)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $functionScore = new FunctionScoreQuery(new MatchAllQuery());
        $functionScore->addScriptScoreFunction("doc['price'].value");
        $search = $repo->createSearch()->addAggregation($aggregation)->addQuery($functionScore);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $aggregationHits = $results['aggregations']['agg_test-top_hits']['hits']['hits'];

        $ids = [];
        foreach ($aggregationHits as $result) {
            $ids[] = $result['_id'];
        }

        $this->assertEquals($expectedHits, $ids);
    }

    /**
     * Test if aggregation gives the expected results using getter.
     *
     * @param AbstractAggregation $aggregation
     * @param array               $expectedHits
     *
     * @dataProvider getTopHitsAggregationData()
     */
    public function testTopHitsAggregationUsingGetter(AbstractAggregation $aggregation, $expectedHits)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $functionScore = new FunctionScoreQuery(new MatchAllQuery());
        $functionScore->addScriptScoreFunction("doc['price'].value");
        $search = $repo->createSearch()->addAggregation($aggregation)->addQuery($functionScore);
        $results = $repo->execute($search);

        $aggregationHits = $results->getAggregations();

        $ids = [];
        foreach ($aggregationHits as $result) {
            if (is_array($result) || $result instanceof \Traversable) {
                foreach ($result as $doc) {
                    $ids[] = $doc->_id;
                }
            }
        }

        $this->assertEquals($expectedHits, $ids);
    }

    /**
     * Check if top hits aggregation works when it's nested.
     */
    public function testTopHitsAggregationNested()
    {
        $topAggregation = new TopHitsAggregation('test-top_hits');
        $termAggregation = new TermsAggregation('test_term');
        $termAggregation->setField('description');
        $termAggregation->addAggregation($topAggregation);

        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()->addAggregation($termAggregation)->addSort(new Sort('_id', Sort::ORDER_ASC));
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertTrue(isset($results['aggregations']['agg_test_term']['buckets'][0]['agg_test-top_hits']['hits']));
        $result = $results['aggregations']['agg_test_term']['buckets'][0]['agg_test-top_hits']['hits'];
        $this->assertEquals(2, $result['total']);

        $this->assertTrue(isset($results['aggregations']['agg_test_term']['buckets'][1]['agg_test-top_hits']['hits']));
        $result = $results['aggregations']['agg_test_term']['buckets'][1]['agg_test-top_hits']['hits'];
        $this->assertEquals(1, $result['total']);
    }
}
