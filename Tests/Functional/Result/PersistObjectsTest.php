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

        $product = $manager->find('AcmeBarBundle:Product', 'doc1');
        $this->count(2, $product->getRelatedCategories());
    }

    /**
     * Test if we can add more objects into document's "multiple objects" field.
     */
    public function testAppendObject()
    {
        $manager = $this->getManager();

        /** @var Product $product */
        $product = $manager->find('AcmeBarBundle:Product', 'doc1');

        $this->count(2, $product->getRelatedCategories());

        $category = new CategoryObject();
        $category->setTitle('Bar Category');
        $product->addRelatedCategory($category);

        $manager->persist($product);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 'doc1');

        $this->count(3, $product->getRelatedCategories());
    }

    /**
     * Tests the type of int and float parameters retrieved from elasticsearch
     * when objects are created with strings
     */
    public function testType()
    {
        $manager = $this->getManager();

        $product = new Product();
        $product->setId('foo');
        $product->setTitle('Test Document');
        $product->setPrice('8.95');

        $person = new Person();
        $person->setFirstName('name');
        $person->setAge('35');

        $manager->persist($product);
        $manager->persist($person);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 'foo');
        $repo = $manager->getRepository('AcmeBarBundle:Person');
        $person = $repo->findOneBy(['first_name' => 'name']);

        $this->assertInternalType('float', $product->getPrice());
        $this->assertInternalType('integer', $person->getAge());
    }

    /**
     * Tests the type of int and float parameters retrieved from elasticsearch
     * when objects are created with arrays of strings
     */
    public function testParsedTypeWhenGivenArrayToIntField()
    {
        $manager = $this->getManager();

        $prices = [8.95, 2.68, 5.66];
        $ages = [35, 11];
        $product = new Product();
        $product->id = 'foo';
        $product->title ='Test Document';
        $product->price = ['8.95', '2.68', '5.66'];

        $person = new Person();
        $person->firstName ='name';
        $person->age = ['35', '11'];

        $manager->persist($product);
        $manager->persist($person);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 'foo');
        $repo = $manager->getRepository('AcmeBarBundle:Person');
        $person = $repo->findOneBy(['first_name' => 'name']);

        $this->assertEquals($prices, $product->price);
        $this->assertEquals($ages, $person->age);
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

        $r = $manager->commit();

        /** @var Product $product1FromES */
        $product1FromES = $manager->find('AcmeBarBundle:Product', '1');
        /** @var Product $product2FromES */
        $product2FromES = $manager->find('AcmeBarBundle:Product', '2');

        $this->assertEquals($product1->getReleased(), $product1FromES->getReleased()->getTimestamp());
        $this->assertEquals(strtotime($product2->getReleased()), $product2FromES->getReleased()->getTimestamp());
    }
}
