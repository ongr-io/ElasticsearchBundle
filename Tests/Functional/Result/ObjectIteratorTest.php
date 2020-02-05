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
use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Result\ObjectIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class ObjectIteratorTest extends AbstractElasticsearchTestCase
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
            ],
        ];
    }

    /**
     * Iteration test.
     */
    public function testIteration()
    {
        /** @var IndexService $index */
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $document */
        $document = $index->find(1);

        $this->assertInstanceOf(ObjectIterator::class, $document->getNestedCollection());

        foreach ($document->getNestedCollection() as $obj) {
            $this->assertInstanceOf(CollectionNested::class, $obj);
        }
    }
}
