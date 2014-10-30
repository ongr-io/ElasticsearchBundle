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

use ONGR\ElasticsearchBundle\DSL\Query\SimpleQueryStringQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class SimpleQueryStringTest extends ElasticsearchTestCase
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
                        'price' => 100,
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 10,
                        'description' => 'foo baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testSimpleQueryStringQuery().
     *
     * @return array
     */
    public function getTestSimpleQueryStringQueryData()
    {
        $out = [];

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 default OR operator - returns both products.
        $out[] = ['foo baz', [], array_reverse($testProducts)];

        // Case #1 AND operator should return second product only.
        $out[] = ['foo baz', ['default_operator' => 'and'], [$testProducts[1]]];

        // Case #2 AND statement search only in description field should return empty array.
        $out[] = ['foo bar', ['fields' => ['description'], 'default_operator' => 'and'], []];

        return $out;
    }

    /**
     * Test simple query string for expected search result.
     *
     * @param string $query
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestSimpleQueryStringQueryData
     */
    public function testSimpleQueryStringQuery($query, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $matchQuery = new SimpleQueryStringQuery($query, $parameters);

        $search = $repo->createSearch()->addQuery($matchQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
