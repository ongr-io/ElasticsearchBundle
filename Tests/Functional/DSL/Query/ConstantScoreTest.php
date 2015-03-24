<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DSL\Query;

use Ongr\ElasticsearchBundle\DSL\Query\ConstantScoreQuery;
use Ongr\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Constant score query functional test.
 */
class ConstantScoreTest extends ElasticsearchTestCase
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
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => 'foo baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test constant score query for expected search result.
     */
    public function testConstantScoreQuery()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $constantScoreQuery = new ConstantScoreQuery(new MatchAllQuery());

        $search = $repo->createSearch()->addQuery($constantScoreQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }
        $expected = array_reverse($testProducts);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
