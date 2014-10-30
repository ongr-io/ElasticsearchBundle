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

use ONGR\ElasticsearchBundle\DSL\Query\FunctionScoreQuery;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * function_score query functional test
 */
class FunctionScoreQueryTest extends ElasticsearchTestCase
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
    public function testFunctionScoreQuery()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $functions = [
            'field_value_factor' => [
                'field' => 'price',
                'factor' => 1.2,
                'modifier' => 'sqrt',
            ]
        ];

        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery(), $functions, ['boost' => 1]);

        $search = $repo->createSearch()->addQuery($functionScoreQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }
        $expected = array_reverse($testProducts);

        $this->assertEquals($expected, $results);
    }
}
