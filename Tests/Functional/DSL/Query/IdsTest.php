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

use ONGR\ElasticsearchBundle\DSL\Query\IdsQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class IdsTest extends ElasticsearchTestCase
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
     * Data provider for testIdsQuery().
     *
     * @return array
     */
    public function getTestIdsQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        $out[] = [[1], [$testProducts[0]]];

        $out[] = [[2, 3], [
            $testProducts[2],
            $testProducts[1],
        ]];

        return $out;
    }

    /**
     * Test Ids query for expected search results.
     *
     * @param string $values
     * @param array  $expected
     *
     * @dataProvider getTestIdsQueryData
     */
    public function testIdsQuery($values, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $idsQuery = new IdsQuery($values);
        $search = $repo->createSearch()->addQuery($idsQuery);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        sort($expected);
        sort($results);
        $this->assertEquals($expected, $results);
    }
}
