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
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CollectionNested;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\DummyDocument;

class PersistObjectsTest extends AbstractElasticsearchTestCase
{
//    /**
//     * {@inheritdoc}
//     */
//    protected function getDataArray()
//    {
//        return [
//            DummyDocument::class => [
//                [
//                    '_id' => 1,
//                    'title' => 'Bar foo foo',
//                    'nested_collection' => [
//                        [
//                            'foo' => 'bar',
//                        ],
//                        [
//                            'acme' => 'delta',
//                        ],
//                    ],
//                ],
//                [
//                    '_id' => 2,
//                    'title' => 'Acme bar bar',
//                    'nested_collection' => [
//                        [
//                            'foo' => 'delta',
//                        ],
//                        [
//                            'acme' => 'bar',
//                        ],
//                    ],
//                ],
//            ]
//        ];
//    }

    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testPersistObject()
    {
        $index = $this->getIndex(DummyDocument::class);

        $doc = new DummyDocument();
        $doc->setId(5);
        $doc->title = 'bar bar';

        $nested = new CollectionNested();
        $nested->key = 'acme';
        $nested->value = 'bar';
        $doc->nestedCollection->add($nested);

        $nested = new CollectionNested();
        $nested->key = 'foo';
        $nested->value = 'delta';
        $doc->nestedCollection->add($nested);

        $index->persist($doc);
        $index->commit();

        $product = $index->find(5);
        $this->assertCount(2, $product->getRelatedCategories());
    }
}
