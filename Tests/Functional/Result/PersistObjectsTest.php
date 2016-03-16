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
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\PrivateId;
use ONGR\ElasticsearchDSL\Query\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;

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

    /**
     * Test if the id is retrieved from elasticsearch
     */
    public function testPersistedId()
    {
        $manager = $this->getManager();
        $product_repo = $manager->getRepository('AcmeBarBundle:Product');
        $private_repo = $manager->getRepository('AcmeBarBundle:PrivateId');
        $product_search = $product_repo->createSearch();
        $private_search = $private_repo->createSearch();

        /** @var Product $product */
        $product = new Product();
        $product->title = 'table';
        $product->description = 'a good product';
        $product->price = 13.25;

        /** @var PrivateId $privateId */
        $private = new PrivateId();
        $private->title = 'private';

        $manager->persist($product);
        $manager->persist($private);
        $manager->commit();

        $query = new MatchQuery('title', 'table');
        $product_search->addQuery($query);
        $product_fetched = $product_repo->execute($product_search);

        $query = new TermQuery('title', 'private');
        $private_search->addQuery($query);
        $private_fetched = $private_repo->execute($private_search);

        $this->assertEquals($product->id, $product_fetched->current()->id);
        $this->assertEquals($private->getId(), $private_fetched->current()->getId());
    }
}
