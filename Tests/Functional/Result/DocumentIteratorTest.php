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

use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class DocumentIteratorTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 'doc1',
                        'title' => 'Foo Product',
                        'url' => [
                            [
                                'url' => 'bar.com',
                                'key' => 'bar_key',
                            ],
                            [
                                'url' => 'acme.com',
                                'key' => 'acme_key',
                            ],
                        ],
                    ],
                    [
                        '_id' => 'doc2',
                        'title' => 'Bar Product',
                        'url' => [
                            [
                                'url' => 'foo.com',
                                'key' => 'foo_key',
                            ],
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
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);
        $iterator = $repo->execute($search, Repository::RESULTS_OBJECT);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\DocumentIterator', $iterator);

        foreach ($iterator as $document) {
            $urls = $document->links;

            $this->assertInstanceOf(
                'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product',
                $document
            );
            $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\ObjectIterator', $urls);

            foreach ($urls as $url) {
                $this->assertInstanceOf(
                    'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\UrlObject',
                    $url
                );
            }
        }
    }
}
