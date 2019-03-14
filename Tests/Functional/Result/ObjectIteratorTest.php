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

use Doctrine\Common\Collections\Collection;
use ONGR\App\Document\CollectionNested;
use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Result\ObjectIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;

class ObjectIteratorTest extends AbstractElasticsearchTestCase
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
