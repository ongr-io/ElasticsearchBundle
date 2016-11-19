<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Result;

use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GetDocumentSortTest extends AbstractElasticsearchTestCase
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
                        '_id' => 'doc1',
                        'title' => 'Foo Product',
                        'price' => 5.00,
                    ],
                    [
                        '_id' => 'doc2',
                        'title' => 'Bar Product',
                        'price' => 8.33,
                    ],
                    [
                        '_id' => 'doc3',
                        'title' => 'Lao Product',
                        'price' => 1.95,
                    ],
                ],
            ],
        ];
    }

    /**
     * GetDocumentSort test.
     */
    public function testGetDocumentSort()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('TestBundle:Product');
        $match = new MatchAllQuery();
        $sort = new FieldSort('price', 'asc');
        $search = $repo->createSearch()->addQuery($match);
        $search->addSort($sort);
        $results = $repo->findDocuments($search);
        $sort_result = [];
        $expected = [1.95, 5, 8.33];

        foreach ($results as $result) {
            $sort_result[] = $results->getDocumentSort();
        }

        $this->assertEquals($sort_result, $expected);
    }
}
