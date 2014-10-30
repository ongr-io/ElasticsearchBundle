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

use ONGR\ElasticsearchBundle\DSL\Query\SpanTermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class SpanTermTest extends ElasticsearchTestCase
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
                        'description' => 'foo bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testSpanTermQuery().
     *
     * @return array
     */
    public function getTestSpanTermQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 'foo' term - should return all products.
        $out[] = ['description', 'foo', $testProducts];

        // Case #1 'baz' term - should return the second product only.
        $out[] = ['description', 'bar', [$testProducts[1]]];

        return $out;
    }

    /**
     * Test span term query for expected search results.
     *
     * @param string $field
     * @param string $value
     * @param array  $expected
     *
     * @dataProvider getTestSpanTermQueryData
     */
    public function testSpanTermQuery($field, $value, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $termQuery = new SpanTermQuery($field, $value);

        $search = $repo->createSearch()->addQuery($termQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
