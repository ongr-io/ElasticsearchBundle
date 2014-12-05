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

use ONGR\ElasticsearchBundle\DSL\Query\MatchQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Match query functional test
 */
class MatchTest extends ElasticsearchTestCase
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
     * Data provider for testMatchQuery().
     *
     * @return array
     */
    public function getTestMatchQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 matches all products.
        $out[] = ['foo', [], $testProducts];

        // Case #1 matches only second product.
        $out[] = ['baz', [], [$testProducts[1]]];

        // Case #2 second products gets higher score.
        $out[] = ['foo baz', [], array_reverse($testProducts)];

        // Case #3 AND argument used matches only second product.
        $out[] = ['foo baz', ['operator' => 'and'], [$testProducts[1]]];

        return $out;
    }

    /**
     * Test match query for expected search result.
     *
     * @param string $query
     * @param string $parameters
     * @param array  $expected
     *
     * @dataProvider getTestMatchQueryData
     */
    public function testMatchQuery($query, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $matchQuery = new MatchQuery($query, 'description', $parameters);

        $search = $repo->createSearch()->addQuery($matchQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
