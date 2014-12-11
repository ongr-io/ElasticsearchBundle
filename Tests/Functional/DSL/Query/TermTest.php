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

use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class TermTest extends ElasticsearchTestCase
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
     * Data provider for testTermQuery().
     *
     * @return array
     */
    public function getTestTermQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Should return the product with price equal to 1000.
        $out[] = ['price', '1000', ['boost' => 1.0], [$testProducts[2]]];

        // There are no products with such title.
        $out[] = ['title', 'foo bar baz', ['boost' => 1.0], []];

        return $out;
    }

    /**
     * Test Term query for expected search results.
     *
     * @param string $field
     * @param string $value
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestTermQueryData
     */
    public function testTermQuery($field, $value, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $termQuery = new TermQuery($field, $value, $parameters);
        $search = $repo->createSearch()->addQuery($termQuery);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals($expected, $results);
    }

    /**
     * Check if we can have multiple term queries in the same bool type.
     */
    public function testMultipleTerm()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $termQuery = new TermQuery('title', 'foo');
        $termQuery2 = new TermQuery('price', 11);
        $search = $repo->createSearch();
        $search->addQuery($termQuery);
        $search->addQuery($termQuery2);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $this->assertEquals(0, count($results));

        $termQuery2 = new TermQuery('price', 10);
        $search = $repo->createSearch();
        $search->addQuery($termQuery);
        $search->addQuery($termQuery2);
        $results = $repo->execute($search, Repository::RESULTS_RAW_ITERATOR);

        $this->assertCount(1, $results);
        $this->assertEquals($results->current()['_id'], 1);
    }
}
