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

use ONGR\ElasticsearchBundle\DSL\Query\CommonTermsQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * CommonTerms query functional test
 */
class CommonTermsTest extends ElasticsearchTestCase
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
     * Data provider for testMoreLikeThisQuery().
     *
     * @return array
     */
    public function getTestCommonTermsQueryData()
    {
        $out = [];
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 low frequency words appear in second and third product ('amet' is of a low frequency).
        $out[] = [
            'adipisicing elit amet',
            [
                'cutoff_frequency' => 0.67,
            ],
            [
                $testProducts[2],
                $testProducts[1],
            ],
        ];

        // Case #1 all three low frequency words appear just in third product.
        $out[] = [
            'adipisicing elit amet',
            [
                'low_freq_operator' => 'and',
                'cutoff_frequency' => 0.67,
            ],
            [
                $testProducts[2],
            ],
        ];

        return $out;
    }

    /**
     * Test CommonTerms query for expected search results.
     *
     * @param string $query
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getTestCommonTermsQueryData
     */
    public function testCommonTermsQuery($query, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $commonTermsQuery = new CommonTermsQuery('description', $query, $parameters);

        $search = $repo->createSearch()->addQuery($commonTermsQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
