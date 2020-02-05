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
use ONGR\App\Document\IndexWithFieldsDataDocument;
use ONGR\ElasticsearchBundle\Result\ArrayIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class ArrayIteratorTest extends AbstractElasticsearchTestCase
{
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'foo',
                    'nested_collection' => [
                        [
                            'key' => 'foo',
                            'value' => 'bar'
                        ],
                        [
                            'key' => 'acme',
                            'value' => 'delta',
                        ],
                    ],
                ],
                [
                    '_id' => 2,
                    'title' => 'foo',
                ],
            ],
            IndexWithFieldsDataDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'foo',
                ],
                [
                    '_id' => 2,
                    'title' => 'bar',
                ],
            ],
        ];
    }

    public function indexDataProvider()
    {
        return [
          [DummyDocument::class],
          //This index is with fields data true setting, the response comes back not in the _source bu _fields instead.
          [IndexWithFieldsDataDocument::class],
        ];
    }

    /**
     * @dataProvider indexDataProvider
     */
    public function testIteration($indexClass)
    {
        /** @var IndexService $index */
        $index = $this->getIndex($indexClass);
        $match = new MatchAllQuery();

        $search = $index->createSearch()->addQuery($match);
        $search->addSort(new FieldSort('_id', FieldSort::ASC));

        $iterator = $index->findArray($search);

        $this->assertInstanceOf(ArrayIterator::class, $iterator);

        $expected = $this->getDataArray()[$indexClass];
        foreach ($iterator as $key => $document) {
            $this->assertEquals($expected[$key], $document);
        }
    }
}
