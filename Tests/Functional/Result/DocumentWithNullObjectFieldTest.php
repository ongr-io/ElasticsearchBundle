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

class DocumentNullObjectFieldTest extends AbstractElasticsearchTestCase
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
                        '_id' => 'Doc 1',
                        'title' => 'Bar Product',
                        'category' => null,
                        'related_categories' => [
                            [
                                'title' => 'Acme',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the returned result with null object field
     */
    public function testResultWithNullObjectField()
    {
        $repo = $this->getManager()->getRepository('AcmeBarBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);
        $iterator = $repo->execute($search);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\DocumentIterator', $iterator);

        foreach ($iterator as $document) {
            $category = $document->category;

            $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\ObjectIterator', $category);
            $this->assertTrue($category->count() == 0);
        }
    }
}
