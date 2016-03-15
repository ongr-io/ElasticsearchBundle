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
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Person;

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
        $category->title = 'Bar Category';

        $category2 = new CategoryObject();
        $category2->title = 'Baz Category';

        $product = new Product();
        $product->id = 'foo';
        $product->title = 'Test Document';
        $product->relatedCategories[] = $category;
        $product->relatedCategories[] = $category2;

        $manager->persist($product);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 'doc1');
        $this->count(2, $product->relatedCategories);
    }

    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testAppendObject()
    {
        $manager = $this->getManager();

        /** @var Product $product */
        $product = $manager->find('AcmeBarBundle:Product', 'doc1');

        $this->count(2, $product->relatedCategories);

        $category = new CategoryObject();
        $category->title = 'Bar Category';
        $product->relatedCategories[] = $category;

        $manager->persist($product);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 'doc1');

        $this->count(3, $product->relatedCategories);
    }

    /**
     * Tests the type of int and float parameters retrieved from elasticsearch
     * when objects are created with strings
     */
    public function testType()
    {
        $manager = $this->getManager();

        $product = new Product();
        $product->id = 'foo';
        $product->title = 'Test Document';
        $product->price = '8.95';

        $person = new Person();
        $person->firstName = 'name';
        $person->age = '35';

        $manager->persist($product);
        $manager->persist($person);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 'foo');
        $repo = $manager->getRepository('AcmeBarBundle:Person');
        $person = $repo->findOneBy(['first_name' => 'name']);

        $this->assertInternalType('float', $product->price);
        $this->assertInternalType('integer', $person->age);
    }
}
