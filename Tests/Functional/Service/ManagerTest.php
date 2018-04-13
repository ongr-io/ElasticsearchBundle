<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Service;

use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\SubcategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends AbstractElasticsearchTestCase
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
                        '_id' => 1,
                        'title' => 'foo',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'acme',
                        'price' => 20,
                    ],
                ],
                'users' => [
                    [
                        '_id' => 1,
                        'first_name' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'first_name' => 'acme',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if persisted objects are flushed.
     */
    public function testPersist()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        $category = new CategoryObject();
        $category->setTitle('acme');

        // Multiple urls.
        $product = new Product();
        $product->setId(1);
        $product->setTitle('test');
        $product->setCategory($category);

        $manager->persist($product);
        $manager->commit();

        // Inheritance
        $subcategory = new SubcategoryObject();
        $subcategory->setTitle('acme');

        $product = new Product();
        $product->setId(1);
        $product->setTitle('test');
        $product->setCategory($subcategory);

        $manager->persist($product);
        $manager->commit();

        /** @var Product $actualProduct */
        $actualProduct = $manager->find('TestBundle:Product', 1);
        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
            $actualProduct
        );

        $this->assertEquals($product->getTitle(), $actualProduct->getTitle());

        /** @var CategoryObject $category */
        $category = $actualProduct->getCategory();
        $this->assertEquals($category->getTitle(), $category->getTitle());

        $this->assertNull($actualProduct->getLimited());

        $actualProduct->setLimited(true);
        $manager->persist($actualProduct);
        $manager->commit();

        $actualProduct = $manager->find('TestBundle:Product', 1);

        $this->assertTrue($actualProduct->getLimited());
    }

    /**
     * Data provider for testPersistExceptions().
     *
     * @return array
     */
    public function getPersistExceptionsData()
    {
        $out = [];

        // Case #0: multiple cdns are put into url object, although it isn't a multiple field.
        $category = new Product();
        $product = new Product;
        $product->setCategory($category);

        $out[] = [
            $product,
            'Expected object of type ' .
            'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject, ' .
            'got ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product.',
        ];

        // Case #1: a single link is set, although field is set to multiple.
        $product = new Product();
        $product->setRelatedCategories(new CategoryObject());
        $out[] = [$product, "must be an instance of Collection"];

        // Case #2: invalid type of object is set to the field.
        $product = new Product;
        $product->setCategory(new \stdClass());
        $out[] = [
            $product,
            'Expected object of type ' .
            'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject, got stdClass.',
        ];

        // Case #3: invalid type of object is set in single field.
        $product = new Product;
        $product->setCategory([new CategoryObject()]);
        $out[] = [
            $product,
            'Expected variable of type object, got array. (field isn\'t multiple)',
        ];

        return $out;
    }

    /**
     * Check if expected exceptions are thrown while trying to persist an invalid object.
     *
     * @param Product $product
     * @param string  $exceptionMessage
     * @param string  $exception
     *
     * @dataProvider getPersistExceptionsData()
     */
    public function testPersistExceptions(
        Product $product,
        $exceptionMessage,
        $exception = 'InvalidArgumentException'
    ) {
        $this->setExpectedException($exception, $exceptionMessage);

        /** @var Manager $manager */
        $manager = $this->getManager();
        $manager->persist($product);
        $manager->commit();
    }

    /**
     * Check if special fields are set as expected.
     */
    public function testPersistSpecialFields()
    {
        $manager = $this->getManager();

        $product = new Product();
        $product->setId('testId');
        $product->setTitle('acme');

        $manager->persist($product);
        $manager->commit();

        $actualProduct = $manager->find('TestBundle:Product', 'testId');

        $this->assertEquals($product->getId(), $actualProduct->getId());
    }

    /**
     * Tests if DateTime object is being parsed.
     */
    public function testPersistDateField()
    {
        $manager = $this->getManager();

        $product = new Product();
        $product->setId('testId');
        $product->setReleased(new \DateTime('2100-01-02 03:04:05.889342'));

        $manager->persist($product);
        $manager->commit();

        $actualProduct = $manager->find('TestBundle:Product', 'testId');

        $this->assertGreaterThan(time(), $actualProduct->getReleased()->getTimestamp());
    }

    /**
     * Tests setter and getter of index name.
     */
    public function testIndexName()
    {
        $uniqueIndexName = 'test_index_' . uniqid();
        $manager = $this->getManager();
        $indexName = $manager->getIndexName();
        $this->assertTrue($manager->getClient()->indices()->exists(['index' => $indexName]));
        $manager->setIndexName($uniqueIndexName);
        $manager->createIndex();
        $this->assertTrue($manager->getClient()->indices()->exists(['index' => $uniqueIndexName]));
        $manager->dropIndex();
        $this->assertFalse($manager->getClient()->indices()->exists(['index' => $uniqueIndexName]));
        $manager->setIndexName($indexName);
        $manager->dropIndex();
        $this->assertFalse($manager->getClient()->indices()->exists(['index' => $indexName]));
    }

    /**
     * Test for getRepository(). Check if local cache is working.
     */
    public function testGetRepository()
    {
        $manager = $this->getManager();
        $expected = $manager->getRepository('TestBundle:Product');
        $repository = $manager->getRepository('TestBundle:Product');

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Service\Repository', $repository);
        $this->assertSame($expected, $repository);
    }

    /**
     * Test for getRepository() in case invalid class name given.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage must be a string
     */
    public function testGetRepositoryException()
    {
        $manager = $this->getManager();
        $manager->getRepository(12345);
    }

    /**
     * Test if search() works with multiple types.
     */
    public function testExecuteQueryOnMultipleTypes()
    {
        $result = $this->getManager()->search(
            ['product', 'users'],
            (new Search())->toArray()
        );

        $this->assertCount(5, $result['hits']['hits']);
    }

    /**
     * Test for remove().
     */
    public function testRemove()
    {
        $manager = $this->getManager();

        $product = $manager->find('TestBundle:Product', 1);
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product', $product);

        $manager->remove($product);
        $manager->commit();

        $product = $manager->find('TestBundle:Product', 1);
        $this->assertNull($product);
    }

    /**
     * Test for remove() in case document has no annotation for ID field.
     *
     * @expectedException \ONGR\ElasticsearchBundle\Exception\MissingDocumentAnnotationException
     * @expectedExceptionMessage "stdClass" class cannot be parsed as document because @Document annotation is missing.
     */
    public function testRemoveException()
    {
        $this->getManager()->remove(new \stdClass());
    }

    /**
     * Tests testParseResults method with different result types
     */
    public function testParseResultsWithDifferentResultTypes()
    {
        $manager = $this->getManager();

        $repo = $manager->getRepository('TestBundle:Product');
        $search = $repo->createSearch();
        $search->addQuery(new MatchAllQuery());
        $products = $repo->findArray($search);
        $this->assertArrayHasKey(0, $products);

        $repo = $manager->getRepository('TestBundle:Product');
        $search = $repo->createSearch();
        $search->addQuery(new MatchAllQuery());
        $products = $repo->findRaw($search);
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\RawIterator', $products);
    }

    /**
     * Tests the exception thrown by the commit method
     *
     * @expectedException \ONGR\ElasticsearchBundle\Exception\BulkWithErrorsException
     */
    public function testCommitException()
    {
        $manager = $this->getManager();
        $product = new Product();
        $nestedProduct = new Product();
        $nestedProduct->setTitle('test');
        $product->setTitle($nestedProduct);

        $manager->persist($product);
        $manager->commit();
    }

    /**
     * Tests custom manager with custom directory behaviour.
     */
    public function testCustomManagerWithCustomMappingDir()
    {
        $manager = $this->getManager('custom_dir');

        $category = new \ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity\CategoryObject();
        $category->title = 'foo';
        $product = new \ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity\Product();
        $product->id = 'custom';
        $product->title = 'Custom product';
        $product->categories[] = $category;
        $manager->persist($product);
        $manager->commit();

        $repo = $manager->getRepository('TestBundle:Product');

        $this->assertEquals(get_class($product), $repo->getClassName());

        /** @var \ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity\Product $actualProduct */
        $actualProduct = $repo->findOneBy(['categories.title' => 'foo']);

        $this->assertEquals('Custom product', $actualProduct->title);
    }

    public function testMultiSearch()
    {
        $manager = $this->getManager();
        $product = new \ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity\Product();
        $product->setId('multi1');
        $product->setTitle('Multi1');
        $manager->persist($product);
        $manager->commit();

        $customManager = $this->getManager('custom_dir');
        $product = new \ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity\Product();
        $product->setId('multi2');
        $product->setTitle('Multi2');
        $customManager->persist($product);
        $customManager->commit();

        $product = new \ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity\Product();
        $product->setId('multi3');
        $product->setTitle('Multi3');

        $customManager->persist($product);
        $customManager->commit();

        $queries = [
            [
                'index' => $manager->getIndexName(),
                'type' => 'product',
            ],
            [
                 'query' => (new MatchQuery('title', 'Multi1'))->toArray()
            ],
            [
                'index' => $customManager->getIndexName()
            ],
            [
                'query' => (new MatchQuery('title', 'Multi2'))->toArray(),
            ],
            [],
            [
                'query' => (new MatchQuery('title', 'Multi3'))->toArray(),
            ]
        ];
        $result = $manager->msearch($queries);

        $this->assertArrayHasKey('responses', $result);
        $this->assertTrue(count($result['responses']) === 3);
    }
}
