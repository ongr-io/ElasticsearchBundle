<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Filter;

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Filter\HasChildFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * HasChild filter functional test.
 */
class HasChildFilterTest extends ElasticsearchTestCase
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
     * Data provider for testHasChildFilter().
     *
     * @return array
     */
    public function getTestHasChildFilterData()
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

        unset($testData['default']['product'][0]['_id']);

        // Case #0: Test with data.
        $filter = new HasChildFilter('comment', new TermQuery('sub_title', 'bar'));

        $out[] = [
            $filter,
            [
                $testData['default']['product'][0],
            ],
            $mapping,
        ];

        // Case #1: Test with no data.
        $filter = new HasChildFilter('comment', new TermQuery('sub_title', 'nobar'));

        $out[] = [
            $filter,
            [],
            $mapping,
        ];

        return $out;
    }

    /**
     * Test Ids filter for expected search results.
     *
     * @param BuilderInterface $filter
     * @param array            $expected
     * @param array            $mapping
     *
     * @dataProvider getTestHasChildFilterData
     */
    public function testHasChildFilter($filter, $expected, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);
        $this->assertEquals($expected, $results);

        $search = $repo->createSearch()->addFilter($filter);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        $this->assertEquals($expected, $results);
    }
}
