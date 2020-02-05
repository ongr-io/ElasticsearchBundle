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
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends AbstractElasticsearchTestCase
{
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'foo'
                ],
                [
                    '_id' => 2,
                    'title' => 'foo',
                ],
                [
                    '_id' => 3,
                    'title' => 'bar',
                ],
            ]
        ];
    }

    public function indexNameDataProvider()
    {
        return [
          [ DummyDocument::class, DummyDocument::INDEX_NAME ],
          // this alias is overriden in configuration
          [ DummyDocumentInTheEntityDirectory::class, 'field-data-index' ],
        ];
    }

    /**
     * @dataProvider indexNameDataProvider
     */
    public function testIndexCrate($class, $indexName)
    {
        $index = $this->getIndex($class);

        $client = $index->getClient();
        $actualIndexExists = $client->indices()->exists(['index' => $indexName]);

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

    public function testIndexConfigOverride()
    {
        $index = $this->getIndex(DummyDocumentInTheEntityDirectory::class);
        $hosts = $index->getIndexSettings()->getHosts();

        $this->assertEquals(['localhost:9200'], $hosts);
    }
}
