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

use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class ArrayIteratorTest extends AbstractElasticsearchTestCase
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
                        'related_categories' => [
                            [
                                'title' => 'Acme',
                            ],
                        ],
                    ],
                    [
                        '_id' => 'doc2',
                        'title' => 'Bar Product',
                        'related_categories' => [
                            [
                                'title' => 'Acme',
                            ],
                            [
                                'title' => 'Bar',
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
        $repo = $this->getManager()->getRepository('TestBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);
        $iterator = $repo->findArray($search);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\ArrayIterator', $iterator);

        $assertResults = $this->getDataArray();
        foreach ($iterator as $key => $document) {
            $assertDocument = $assertResults['default']['product'][$key];
            unset($assertDocument['_id']);
            $this->assertEquals($assertDocument, $document);
        }
    }

    /**
     * Test array results iteration with fields set.
     */
    public function testIterationWhenFieldsAreSet()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('TestBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);
        $iterator = $repo->findArray($search);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\ArrayIterator', $iterator);

        $assertResults = $this->getDataArray();
        foreach ($iterator as $key => $document) {
            $this->assertEquals($assertResults['default']['product'][$key]['title'], $document['title']);
        }
    }
}
