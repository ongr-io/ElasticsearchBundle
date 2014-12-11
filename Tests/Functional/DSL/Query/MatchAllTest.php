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

use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class MatchAllTest extends ElasticsearchTestCase
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
                        'price' => 10,
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'foo baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testMatchAllQuery().
     *
     * @return array[]
     */
    public function getMatchAllData()
    {
        // Case #0 with boost.
        $out[] = [
            [
                'boost' => 1.0,
            ],
        ];

        return $out;
    }

    /**
     * Test Match All query for expected search results.
     *
     * @param array $parameters Additional parameters.
     *
     * @dataProvider getMatchAllData()
     */
    public function testMatchAllQuery($parameters)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $matchAllQuery = new MatchAllQuery($parameters);
        $search = $repo->createSearch()->addQuery($matchAllQuery);

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
