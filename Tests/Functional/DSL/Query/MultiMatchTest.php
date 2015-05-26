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

use ONGR\ElasticsearchBundle\DSL\Query\MultiMatchQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Multi match query functional test.
 */
class MultiMatchTest extends ElasticsearchTestCase
{
    /**
     * @internal param array $data Example:
     * Example:
     *      "default" =>
     *      [
     *          'product' => [
     *              [
     *                  '_id' => 1,
     *                  'title' => 'foo',
     *                  'description' => 'Lorem ipsum',
     *                  'description2' => 'Lorem ipsum',
     *              ]
     *          ]
     *      ]
     *
     * @return array
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
                        'description2' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'Lorem ipsum dolor sit amet...',
                        'description2' => 'Lorem ipsum',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                        'description2' => 'Lorem ipsum',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testMultiMatchQuery().
     *
     * @return array
     */
    public function getTestMultiMatchQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Should return the product with price equal to 1000.
        $out[] = [
            'Lorem ipsum',
            ['description', 'description2'],
            [
                $testProducts[2],
                $testProducts[1],
                $testProducts[0],
            ],
        ];

        return $out;
    }

    /**
     * Test MultiMatch query for expected search results.
     *
     * @param string $query
     * @param array  $fields
     * @param array  $expected
     *
     * @dataProvider getTestMultiMatchQueryData
     */
    public function testMultiMatchQuery($query, $fields, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $multiMatchQuery = new MultiMatchQuery($fields, $query);

        $search = $repo->createSearch()->addQuery($multiMatchQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
