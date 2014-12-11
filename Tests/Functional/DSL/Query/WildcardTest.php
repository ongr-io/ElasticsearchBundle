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

use ONGR\ElasticsearchBundle\DSL\Query\WildcardQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class WildcardTest extends ElasticsearchTestCase
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
                        'description' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

     /**
      * Data provider for testWildcardQuery().
      *
      * @return array
      */
    public function getTestWildcardQueryData()
    {
        $out = [];

        $testData = $this->getDataArray();
        unset($testData['default']['product'][1]['_id']);
        unset($testData['default']['product'][2]['_id']);

        // Should return the product with price equal to 1000.
        $out[] = ['title', 'b*r', ['boost' => 1.0], [$testData['default']['product'][1]]];

        // Both bar and baz fit the condition.
        $out[] = [
            'title',
            'b**',
            [
                'boost' => 1.0,
            ],
            [
                $testData['default']['product'][2],
                $testData['default']['product'][1],
            ],
        ];

        return $out;
    }

    /**
     * Test Wildcard query for expected search results.
     *
     * @param string $field
     * @param string $value
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestWildcardQueryData
     */
    public function testWildcardQuery($field, $value, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $wildcardQuery = new WildcardQuery($field, $value, $parameters);
        $search = $repo->createSearch()->addQuery($wildcardQuery);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
