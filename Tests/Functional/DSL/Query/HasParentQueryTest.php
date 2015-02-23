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

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Query\HasParentQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * HasParent query functional test.
 */
class HasParentQueryTest extends ElasticsearchTestCase
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
                    ],
                ],
                'comment' => [
                    [
                        '_parent' => 1,
                        'sub_title' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testHasChildQuery().
     *
     * @return array
     */
    public function getTestHasParentQueryData()
    {
        $out = [];
        $testData = $this->getDataArray();

        $mapping = [
            'product' => [
                'properties' => [
                    'title' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'comment' => [
                '_parent' => [
                    'type' => 'product',
                ],
                '_routing' => [
                    'required' => true,
                ],
                'properties' => [
                    'sub_title' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];

        unset($testData['default']['comment'][0]['_parent']);

        // Case #0: Test with data.
        $query = new HasParentQuery('product', new TermQuery('title', 'foo'));

        $out[] = [
            $query,
            [
                $testData['default']['comment'][0],
            ],
            $mapping,
        ];

        // Case #1: Test with no data.
        $query = new HasParentQuery('product', new TermQuery('title', 'nofoo'));

        $out[] = [
            $query,
            [],
            $mapping,
        ];

        return $out;
    }

    /**
     * Test has_child query for expected search results.
     *
     * @param BuilderInterface $query
     * @param array            $expected
     * @param array            $mapping
     *
     * @dataProvider getTestHasParentQueryData
     */
    public function testHasParentQuery($query, $expected, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Comment');
        $search = $repo->createSearch()->addQuery($query, 'must');
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);
        $this->assertEquals($expected, $results);
    }
}
