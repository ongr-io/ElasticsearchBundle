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

use ONGR\ElasticsearchBundle\DSL\Aggregation\Ipv4RangeAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Function tests for ipv4 aggregation.
 */
class Ipv4RangeAggregationTest extends AbstractElasticsearchTestCase
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
                        'ip' => '10.0.0.2',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'ip' => '10.0.0.6',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'pizza',
                        'ip' => '10.0.0.8',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test if Ipv4 range aggregations works as expected.
     */
    public function testIpv4Aggregation()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new Ipv4RangeAggregation('ip_ranges');
        $aggregation->setField('ip');
        $aggregation->addRange('10.0.0.1', '10.0.0.4');
        $search = $repo->createSearch()->addAggregation($aggregation);
        $expectedResult = [
            'agg_ip_ranges' => [
                'buckets' => [
                    [
                        'key' => '10.0.0.1-10.0.0.4',
                        'from' => 167772161,
                        'from_as_string' => '10.0.0.1',
                        'to' => 167772164,
                        'to_as_string' => '10.0.0.4',
                        'doc_count' => 1,
                    ],
                ],
            ],
        ];
        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertEquals($expectedResult, $results['aggregations']);
    }

    /**
     * Test ipv4 range aggregation with mask range.
     */
    public function testIpv4RangeAggregationWithMask()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $aggregation = new Ipv4RangeAggregation('ip_ranges');
        $aggregation->setField('ip');
        $aggregation->addMask('10.0.0.0/25');

        $search = $repo->createSearch()->addAggregation($aggregation);
        $expectedResult = [
            'agg_ip_ranges' => [
                'buckets' => [
                    [
                        'key' => '10.0.0.0/25',
                        'from' => 167772160,
                        'from_as_string' => '10.0.0.0',
                        'to' => 167772288,
                        'to_as_string' => '10.0.0.128',
                        'doc_count' => 3,
                    ],
                ],
            ],
        ];

        $results = $repo->execute($search, Repository::RESULTS_RAW);
        $this->assertEquals($expectedResult, $results['aggregations']);
    }
}
