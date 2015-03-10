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

use ONGR\ElasticsearchBundle\DSL\Aggregation\DateRangeAggregation;
use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class DateRangeAggregationTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'comment' => [
                    [
                        '_parent' => 1,
                        'sub_title' => 'foo',
                        'createdAt' => '2010',
                    ],
                    [
                        '_parent' => 1,
                        'sub_title' => 'bar bar',
                        'createdAt' => '2011',
                    ],
                    [
                        '_parent' => 2,
                        'sub_title' => 'foo bar',
                        'createdAt' => '2013',
                    ],
                    [
                        '_parent' => 2,
                        'sub_title' => 'bar',
                        'createdAt' => '2015',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test when one range is set and from is null.
     */
    public function testDateRangeWhenFromIsNull()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Comment');
        $aggregation = new DateRangeAggregation('date_range');
        $aggregation->setField('createdAt');
        $aggregation->setFormat('Y');
        $aggregation->addRange(null, '2011');

        $expectedResults = [
            'agg_date_range' => [
                'buckets' => [
                    [
                        'key' => '*-2011',
                        'to' => '1293840000000',
                        'to_as_string' => '2011',
                        'doc_count' => 1,
                    ],
                ],
            ],
        ];

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResults, $results['aggregations']);
    }

    /**
     * Test when multiple ranges are set and to is null.
     */
    public function testDateRangeWhenToIsNull()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Comment');
        $aggregation = new DateRangeAggregation('date_range');
        $aggregation->setField('createdAt');
        $aggregation->setFormat('Y');
        $aggregation->addRange('2011', '2015');
        $aggregation->addRange('2014', null);

        $expectedResults = [
            'agg_date_range' => [
                'buckets' => [
                    [
                        'key' => '2011-2015',
                        'from' => '1293840000000',
                        'from_as_string' => '2011',
                        'to' => '1420070400000',
                        'to_as_string' => '2015',
                        'doc_count' => 2,
                    ],
                    [
                        'key' => '2014-*',
                        'from' => '1388534400000',
                        'from_as_string' => '2014',
                        'doc_count' => 1,
                    ],
                ],
            ],
        ];

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertArrayHasKey('aggregations', $results);
        $this->assertEquals($expectedResults, $results['aggregations']);
    }
}
