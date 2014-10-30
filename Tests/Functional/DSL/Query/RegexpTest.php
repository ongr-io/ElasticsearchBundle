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

use ONGR\ElasticsearchBundle\DSL\Query\RegexpQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Regexp query functional test
 */
class RegexpTest extends ElasticsearchTestCase
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
                        'title' => 'bar',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testRegexpQuery().
     *
     * @return array
     */
    public function getTestRegexpQueryData()
    {
        $out = [];

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 matches the only product with title 'baz'.
        $out[] = ['baz', ['flags' => 'NONE'], [$testProducts[1]]];

        // Case #1 matches all product titles beginning with 'ba'.
        $out[] = ['ba.*', [], array_reverse($testProducts)];

        return $out;
    }

    /**
     * Test regexp query for expected search result.
     *
     * @param string $regexp
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestRegexpQueryData
     */
    public function testRegexpQuery($regexp, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $matchQuery = new RegexpQuery('title', $regexp, $parameters);

        $search = $repo->createSearch()->addQuery($matchQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }
}
