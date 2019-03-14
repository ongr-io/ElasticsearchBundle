<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Service;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends AbstractElasticsearchTestCase
{
    protected function getDataArray()
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'foo',
                    'nested_collection' => [
                        [
                            'foo' => 'bar',
                        ],
                        [
                            'acme' => 'delta',
                        ],
                    ],
                ],
                [
                    '_id' => 2,
                    'title' => 'foo',
                    'nested_collection' => [
                        [
                            'foo' => 'delta',
                        ],
                        [
                            'acme' => 'bar',
                        ],
                    ],
                ],
                [
                    '_id' => 3,
                    'title' => 'bar',
                    'nested_collection' => [
                        [
                            'foo' => 'delta',
                        ],
                        [
                            'acme' => 'bar',
                        ],
                    ],
                ],
            ]
        ];
    }

    public function testIndexCrate()
    {
        $index = $this->getIndex(DummyDocument::class);

        $client = $index->getClient();
        $actualIndexExists = $client->indices()->exists(['index' => DummyDocument::INDEX_NAME]);

        $this->assertTrue($actualIndexExists);
    }

    public function testDocumentInsertAndFindById()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $document */
        $document = $index->find(3);

        $this->assertEquals('bar', $document->title);
    }

    public function testFindOneBy()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $result */
        $result = $index->findOneBy(['title.raw' => 'bar']);
        $this->assertInstanceOf(DummyDocument::class, $result);
        $this->assertEquals(3, $result->id);
    }

    public function testFind()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $result */
        $result = $index->find(3);
        $this->assertInstanceOf(DummyDocument::class, $result);
        $this->assertEquals(3, $result->id);
        $this->assertEquals('bar', $result->title);
    }

    public function testFindBy()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DocumentIterator $result */
        $result = $index->findBy(['title.raw' => 'foo']);
        $this->assertInstanceOf(DocumentIterator::class, $result);
        $this->assertEquals(2, $result->count());

        $actualList = [];
        /** @var DummyDocument $item */
        foreach ($result as $item) {
            $actualList[] = (int) $item->id;
        }
        sort($actualList);

        $this->assertEquals([1,2], $actualList);
    }
}
