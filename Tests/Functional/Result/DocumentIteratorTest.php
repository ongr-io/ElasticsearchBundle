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

use ONGR\App\Document\CollectionNested;
use ONGR\App\Document\CollectionObject;
use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Result\ObjectIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class DocumentIteratorTest extends AbstractElasticsearchTestCase
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
                    'object_collection' => [
                        [
                            'title' => 'acme',
                        ],
                        [
                            'title' => 'foo',
                        ],
                        [
                            'title' => 'bar',
                        ],
                    ],
                ],
                [
                    '_id' => 2,
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
                    'object_collection' => [
                        [
                            'title' => 'acme',
                        ],
                        [
                            'title' => 'foo',
                        ],
                        [
                            'title' => 'bar',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testIteration()
    {
        /** @var IndexService $index */
        $index = $this->getIndex(DummyDocument::class);
        $match = new MatchAllQuery();
        $search = $index->createSearch()->addQuery($match);
        $iterator = $index->findDocuments($search);

        $this->assertInstanceOf(DocumentIterator::class, $iterator);

        /** @var DummyDocument $document */
        foreach ($iterator as $document) {
            $this->assertInstanceOf(DummyDocument::class, $document);

            $collection = $document->getNestedCollection();
            $this->assertInstanceOf(ObjectIterator::class, $collection);

            foreach ($collection as $obj) {
                $this->assertInstanceOf(CollectionNested::class, $obj);
            }

            $collection = $document->getObjectCollection();
            $this->assertInstanceOf(ObjectIterator::class, $collection);

            foreach ($collection as $obj) {
                $this->assertInstanceOf(CollectionObject::class, $obj);
            }
        }
    }
}
