<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Filter\RangeFilter;
use ONGR\ElasticsearchBundle\DSL\Query\FunctionScoreQuery;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Function_score query functional test.
 */
class FunctionScoreQueryTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getIgnoredVersions()
    {
        return [
            [ '1.4.0', '<=' ],
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
                        'price' => 20,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 50,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'buz',
                        'price' => 100,
                    ],
                    [
                        '_id' => 4,
                        'title' => 'doo',
                        'price' => 200,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test function_score query for expected search result.
     */
    public function testFieldValueFactorFunction()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery(), ['boost' => 1]);
        $functionScoreQuery->addFieldValueFactorFunction('price', 1.2, 'sqrt');

        $search = $repo->createSearch()->addQuery($functionScoreQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }
        $expected = array_reverse($testProducts);

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests functions score function with included filter.
     */
    public function testWeightFunctionWithFilter()
    {
        $weight = 5;

        $condition = 100;

        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $filter = new RangeFilter('price', ['lte' => $condition]);
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery(), ['boost' => 1]);
        $functionScoreQuery->setDslType('filter');

        $functionScoreQuery->addWeightFunction($weight, $filter);
        $search = $repo->createSearch()->addQuery($functionScoreQuery);

        $results = $repo->execute($search, Repository::RESULTS_RAW_ITERATOR);

        foreach ($results as $result) {
            if ($weight == $result['_score']) {
                $this->assertLessThanOrEqual($condition, $result['_source']['price']);
            } else {
                $this->assertGreaterThanOrEqual($condition, $result['_source']['price']);
            }
        }
    }
}
