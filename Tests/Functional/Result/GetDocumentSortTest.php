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

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class GetDocumentSortTest extends AbstractElasticsearchTestCase
{
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 'doc1',
                    'title' => 'Foo Product',
                    'number' => 5.00,
                ],
                [
                    '_id' => 'doc2',
                    'title' => 'Bar Product',
                    'number' => 8.33,
                ],
                [
                    '_id' => 'doc3',
                    'title' => 'Lao Product',
                    'number' => 1.95,
                ],
            ],
        ];
    }

    public function testGetDocumentSort()
    {
        $index = $this->getIndex(DummyDocument::class);

        $match = new MatchAllQuery();
        $sort = new FieldSort('number', 'asc');
        $search = $index->createSearch()->addQuery($match);
        $search->addSort($sort);

        $results = $index->findDocuments($search);

        $sort_result = [];
        $expected = [1.95, 5, 8.33];

        foreach ($results as $result) {
            $sort_result[] = $results->getDocumentSort();
        }

        $this->assertEquals($sort_result, $expected);
    }
}
