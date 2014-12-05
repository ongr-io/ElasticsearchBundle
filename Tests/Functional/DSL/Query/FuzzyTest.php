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

use ONGR\ElasticsearchBundle\DSL\Query\FuzzyQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class FuzzyTest extends ElasticsearchTestCase
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
     * Data provider for testFuzzyQuery().
     *
     * @return array
     */
    public function getTestFuzzyQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Should return the product with price in range [990..1010].
        $out[] = ['price', 1000, ['fuzziness' => 10], [$testProducts[2]]];

        // Should return the product with price in range [-800..1000].
        $out[] = ['price', 100, ['fuzziness' => 900], [
            $testProducts[2],
            $testProducts[1],
            $testProducts[0],
        ]];

        return $out;
    }

    /**
     * Test Fuzzy query for expected search results.
     *
     * @param string $field
     * @param string $value
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestFuzzyQueryData
     */
    public function testFuzzyQuery($field, $value, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $fuzzyQuery = new FuzzyQuery($field, $value, $parameters);
        $search = $repo->createSearch()->addQuery($fuzzyQuery);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        sort($expected);
        sort($results);
        $this->assertEquals($expected, $results);
    }
}
