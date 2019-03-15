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

        $index->persist($document);
        $index->commit();

        $document = $index->find(5);
        $this->assertEquals('bar bar', $document->title);
    }
}
