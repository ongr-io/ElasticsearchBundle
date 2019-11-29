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

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\App\Document\IndexWithFieldsDataDocument;
use ONGR\App\Document\CollectionNested;
use ONGR\App\Document\DummyDocument;

class PersistObjectsTest extends AbstractElasticsearchTestCase
{
    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testPersistObject()
    {
        $index = $this->getIndex(DummyDocument::class);

        $document = new DummyDocument();
        $document->id = 5;
        $document->title = 'bar bar';

        $nested = new CollectionNested();
        $nested->key = 'acme';
        $nested->value = 'bar';
        $document->getNestedCollection()->add($nested);

        $nested = new CollectionNested();
        $nested->key = 'foo';
        $nested->value = 'delta';
        $document->getNestedCollection()->add($nested);

        $document->setDatetimefield(new \DateTime('2010-01-01 10:10:56'));
        $index->persist($document);
        $index->commit();

        $document = $index->find(5);
        $this->assertEquals('bar bar', $document->title);
        $this->assertInstanceOf(\DateTimeInterface::class, $document->getDatetimefield());
        $this->assertEquals('2010-01-01', $document->getDatetimefield()->format('Y-m-d'));
    }

    public function testAddingValuesToPrivateIdsWithoutSetters()
    {
        $index = $this->getIndex(IndexWithFieldsDataDocument::class);

        $document = new IndexWithFieldsDataDocument();
        $document->title = 'acme';

        $index->persist($document);
        $index->commit();

        $document = $index->findOneBy(['private' => 'acme']);
        $this->assertNotNull($document->getId());
    }
}
