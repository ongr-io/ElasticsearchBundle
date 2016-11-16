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
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;

class PersistObjectsTest extends AbstractElasticsearchTestCase
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
                ],
            ],
        ];
    }

    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testPersistMultipleObjects()
    {
        $manager = $this->getManager();

        $category = new CategoryObject();
        $category->setTitle('Bar Category');

        $category2 = new CategoryObject();
        $category2->setTitle('Baz Category');

        $product = new Product();
        $product->setId('foo');
        $product->setTitle('Test Document');
        $product->addRelatedCategory($category);
        $product->addRelatedCategory($category2);

        $manager->persist($product);
        $manager->commit();

        $product = $manager->find('TestBundle:Product', 'doc1');
        $this->assertCount(2, $product->getRelatedCategories());
    }

    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testAppendObject()
    {
        $manager = $this->getManager();

        /** @var Product $product */
        $product = $manager->find('TestBundle:Product', 'doc1');

        $this->assertCount(2, $product->getRelatedCategories());

        $category = new CategoryObject();
        $category->setTitle('Bar Category');
        $product->addRelatedCategory($category);

        $manager->persist($product);
        $manager->commit();

        $product = $manager->find('TestBundle:Product', 'doc1');

        $this->assertCount(3, $product->getRelatedCategories());
    }

    /**
     * Tests the type of int and float parameters retrieved from elasticsearch
     * when objects are created with arrays of strings
     */
    public function testParsedTypeWhenGivenArrayToFloatField()
    {
        $manager = $this->getManager();

        $prices = [8.95, 2.68, 5.66];
        $product = new Product();
        $product->setId('foo');
        $product->setTitle('Test Document');
        $product->setPrice(['8.95', '2.68', '5.66']);

        $manager->persist($product);
        $manager->commit();

        $product = $manager->find('TestBundle:Product', 'foo');

        $this->assertEquals($prices, $product->getPrice());
    }

    public function testDocumentPersistWithDate()
    {
        $product1 = new Product();
        $product1->setId('1');
        $product1->setReleased('1458206100');

        $product2 = new Product();
        $product2->setId('2');
        $product2->setReleased('2016-11-11T11:22:11');

        $manager = $this->getManager();
        $manager->persist($product1);
        $manager->persist($product2);

        $manager->commit();

        /** @var Product $product1FromES */
        $product1FromES = $manager->find('TestBundle:Product', '1');
        /** @var Product $product2FromES */
        $product2FromES = $manager->find('TestBundle:Product', '2');

        $this->assertEquals($product1->getReleased(), $product1FromES->getReleased()->getTimestamp());
        $this->assertEquals(strtotime($product2->getReleased()), $product2FromES->getReleased()->getTimestamp());
    }

    public function testPersistAndRemoveDocumentWithRouting()
    {
        $product1 = new Product();
        $product2 = new Product();
        $manager = $this->getManager();
        $repo = $manager->getRepository('TestBundle:Product');

        $product1->setId('1');
        $product1->setRouting('foo');
        $product2->setId('2');
        $product2->setRouting('foo');

        $manager->persist($product1);
        $manager->persist($product2);
        $manager->commit();

        $this->assertNull($repo->find('1'));
        $this->assertNull($repo->find('2'));
        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
            $repo->find('1', 'foo')
        );
        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
            $repo->find('2', 'foo')
        );

        $repo->remove('1', 'foo');
        $manager->remove($product2);
        $manager->commit();

        $this->assertNull($repo->find('1', 'foo'));
        $this->assertNull($repo->find('2', 'foo'));
    }
}
