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

use ONGR\ElasticsearchBundle\DSL\Aggregation\SignificantTermsAggregation;
use ONGR\ElasticsearchBundle\DSL\Filter\PrefixFilter;
use ONGR\ElasticsearchBundle\DSL\Query\TermsQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class SignificantTermsAggregationTest extends AbstractElasticsearchTestCase
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
                        'title' => ['foo', 'bar', 'foo bar', 'kar'],
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => ['foo', 'fooo', 'barbar'],
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => ['foo'],
                        'price' => 15.1,
                    ],
                ],
                'item' => [
                    [
                        '_id' => 1,
                        'name' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'name' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testSignificantTermsAggregation().
     *
     * @return array
     */
    public function getSignificantTermsAggregationData()
    {
        $out = [];

        // Case #0 significant term aggregation with min_doc_count, size, mutual_information, execution_hint, jlh set.
        $aggregationData = array_filter(
            [
                'shard_min_doc_count' => null,
                'size' => 2,
                'shard_size' => null,
                'background_filter' => null,
                'execution_hint' => SignificantTermsAggregation::HINT_GLOBAL_ORDINALS,
                'percentage' => null,
                'chi_square' => null,
                'mutual_information' => ['include_negatives' => true, 'background_is_superset' => false],
                'min_doc_count' => 1,
                'jlh' => true,
                'gnd' => null,
            ]
        );
        $expectedResults = [
            'agg_test_agg' => [
                'doc_count' => 3,
                'buckets' => [
                    ['key' => 'foo', 'doc_count' => 3, 'score' => 0.67, 'bg_count' => 3],
                    ['key' => 'kar', 'doc_count' => 1, 'score' => 0.22, 'bg_count' => 1],
                ],
            ],
        ];
        $out[] = [$aggregationData, $expectedResults];

        // Case #1 significant term aggregation test with background_filter, chi_square, gnd set.
        $aggregationData = array_filter(
            [
                'shard_min_doc_count' => null,
                'size' => null,
                'shard_size' => null,
                'background_filter' => new PrefixFilter('name', 'fo'),
                'execution_hint' => null,
                'percentage' => null,
                'chi_square' => ['include_negatives' => true, 'background_is_superset' => false],
                'mutual_information' => null,
                'min_doc_count' => null,
                'jlh' => null,
                'gnd' => ['background_is_superset' => false],
            ]
        );

        $expectedResults = [
            'agg_test_agg' => [
                'doc_count' => 3,
                'buckets' => [
                    ['key' => 'foo', 'doc_count' => 3, 'score' => 1, 'bg_count' => 0],
                ],
            ],
        ];
        $out[] = [$aggregationData, $expectedResults];

        // Case #2 significant term aggregation test with shard_min_doc_count, shard_size.
        $aggregationData = array_filter(
            [
                'shard_min_doc_count' => 2,
                'size' => null,
                'shard_size' => 5,
                'background_filter' => null,
                'execution_hint' => null,
                'percentage' => null,
                'chi_square' => null,
                'mutual_information' => null,
                'min_doc_count' => null,
                'jlh' => null,
                'gnd' => null,
            ]
        );
        $expectedResults = [
            'agg_test_agg' => [
                'doc_count' => 3,
                'buckets' => [],
            ],
        ];

        $out[] = [$aggregationData, $expectedResults];

        return $out;
    }

    /**
     * Test for significant terms aggregation.
     *
     * @param array $aggregationData
     * @param array $expectedResults
     *
     * @dataProvider getSignificantTermsAggregationData
     */
    public function testSignificantTermsAggregation($aggregationData, $expectedResults)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $testTerm = new TermsQuery('title', ['foo']);
        $agg = new SignificantTermsAggregation('test_agg');
        $agg->setField('title');

        if (array_key_exists('shard_min_doc_count', $aggregationData)) {
            $agg->setShardMinDocCount($aggregationData['shard_min_doc_count']);
        }

        if (array_key_exists('size', $aggregationData)) {
            $agg->setSize($aggregationData['size']);
        }

        if (array_key_exists('shard_size', $aggregationData)) {
            $agg->setShardSize($aggregationData['shard_size']);
        }

        if (array_key_exists('background_filter', $aggregationData)) {
            $agg->setBackgroundFilter($aggregationData['background_filter']);
        }

        if (array_key_exists('execution_hint', $aggregationData)) {
            $agg->setExecutionHint($aggregationData['execution_hint']);
        }

        if (array_key_exists('percentage', $aggregationData)) {
            $agg->setPercentage($aggregationData['percentage']);
        }

        if (array_key_exists('chi_square', $aggregationData)) {
            $agg->setChiSquare(
                $aggregationData['chi_square']['include_negatives'],
                $aggregationData['chi_square']['background_is_superset']
            );
        }

        if (array_key_exists('mutual_information', $aggregationData)) {
            $agg->setMutualInformation(
                $aggregationData['mutual_information']['include_negatives'],
                $aggregationData['mutual_information']['background_is_superset']
            );
        }

        if (array_key_exists('min_doc_count', $aggregationData)) {
            $agg->setMinDocCount($aggregationData['min_doc_count']);
        }

        if (array_key_exists('jlh', $aggregationData)) {
            $agg->setJlh($aggregationData['jlh']);
        }

        if (array_key_exists('gnd', $aggregationData)) {
            $agg->setGnd($aggregationData['gnd']['background_is_superset']);
        }

        if (array_key_exists('background_is_superset', $aggregationData)) {
            $agg->setGnd($aggregationData['gnd']['background_is_superset']);
        }

        $search = $repo->createSearch()->addQuery($testTerm)->addAggregation($agg);
        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertEquals($expectedResults, $results['aggregations'], '', 0.01);
    }
}
