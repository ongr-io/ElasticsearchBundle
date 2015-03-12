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
use ONGR\ElasticsearchBundle\DSL\Filter\TermFilter;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Filter\HasParentFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * HasParent filter functional test.
 */
class HasParentFilterTest extends AbstractElasticsearchTestCase
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
    public function getTestHasParentFilterData()
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
        $filter = new HasParentFilter('product', new TermFilter('title', 'foo'));

        $out[] = [
            $filter,
            [
                $testData['default']['comment'][0],
            ],
            $mapping,
        ];

        // Case #1: Test with no data.
        $filter = new HasParentFilter('product', new TermQuery('title', 'nofoo'));
        $filter->setDslType('query');

        $out[] = [
            $filter,
            [],
            $mapping,
        ];

        return $out;
    }

    /**
     * Test has_parent filter for expected search results.
     *
     * @param BuilderInterface $filter
     * @param array            $expected
     * @param array            $mapping
     *
     * @dataProvider getTestHasParentFilterData
     */
    public function testHasParentFilter($filter, $expected, $mapping)
    {
        /** @var Repository $repo */
        $repo = $this->getManager('default', true, $mapping)->getRepository('AcmeTestBundle:Comment');
        $search = $repo->createSearch()->addFilter($filter, 'must');
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        sort($results);
        sort($expected);
        $this->assertEquals($expected, $results);
    }
}
