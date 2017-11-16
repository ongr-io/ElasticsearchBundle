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
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class ObjectIteratorTest extends AbstractElasticsearchTestCase
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
                                'color' => 'blue',
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
     * @param string $id
     *
     * @return Collection
     */
    protected function getCategoriesForProduct($id)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('TestBundle:Product');

        /** @var Product $document */
        $document = $repo->find($id);

        return $document->getRelatedCategories();
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
        $iterator = $repo->findDocuments($search);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\DocumentIterator', $iterator);

        foreach ($iterator as $document) {
            $categories = $document->getRelatedCategories();

            $this->assertInstanceOf(
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
                $document
            );

            $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\ObjectIterator', $categories);
            $this->assertNotNull($categories[0]);
        }
    }

    public function testGetItem()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $this->assertNotNull($categories->get(1));
    }

    public function testGetFirstItem()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $this->assertNotNull($categories->first());
    }

    public function testGetLastItem()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $this->assertNotNull($categories->last());
    }

    public function testGetNextItem()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $this->assertNotNull($categories->next());
    }

    public function testGetCurrentItem()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $this->assertNotNull($categories->current());
    }

    public function testCollectionGetValues()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $values = $categories->getValues();

        foreach ($values as $value) {
            $this->assertInstanceOf(
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject',
                $value
            );
        }
    }

    public function testCollectionMap()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $mapped = $categories->map(function (CategoryObject $category) {
            $category->setTitle($category->getTitle() . '!');
            return $category;
        });

        $this->assertEquals('Acme!', $mapped[0]->getTitle());
    }

    public function testCollectionFilter()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $filtered = $categories->filter(function (CategoryObject $category) {
            return $category->getTitle() === 'Acme';
        });

        $this->assertCount(1, $filtered);
    }

    public function testCollectionToArray()
    {
        $categories = $this->getCategoriesForProduct('doc2');

        $values = $categories->toArray();

        foreach ($values as $value) {
            $this->assertInstanceOf(
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject',
                $value
            );
        }
    }
}
