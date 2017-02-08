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
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;

class DocumentWithMultipleFieldsTest extends AbstractElasticsearchTestCase
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
                    [
                        '_id' => 'doc2',
                        'title' => 'Foo Product',
                    ],
                    [
                        '_id' => 'doc3',
                        'title' => 'Bar Production',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testMultipleFields()
    {
        $repo = $this->getManager()->getRepository('TestBundle:Product');

//        throw new \Exception('Fuck you phpunit');

        $query = new MatchQuery('title', 'Bar');
        $search = $repo->createSearch();
        $search->addQuery($query);
        $result = $repo->findDocuments($search);
        $this->assertEquals(2, count($result));

        $query = new TermQuery('title.raw', 'Bar');
        $search = $repo->createSearch();
        $search->addQuery($query);
        $result = $repo->findDocuments($search);
        $this->assertEquals(0, count($result));

        $query = new TermQuery('title.raw', 'Foo Product');
        $search = $repo->createSearch();
        $search->addQuery($query);
        $result = $repo->findDocuments($search);
        $this->assertEquals(1, count($result));
    }
}
