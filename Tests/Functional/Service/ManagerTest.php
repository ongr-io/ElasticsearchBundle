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

use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\WithoutId;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends AbstractElasticsearchTestCase
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->repository = $this->getManager()->getRepository('AcmeBarBundle:Product');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'foo' => [
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
                'customer' => [
                    [
                        '_id' => 1,
                        'name' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'name' => 'acme',
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
        $manager = $this->repository->getManager();

        $category = new CategoryObject();
        $category->title = 'acme';

        // Multiple urls.
        $product = new Product();
        $product->setId(1);
        $product->title = 'test';
        $product->category = $category;

        $manager->persist($product);
        $manager->commit();

        /** @var Product $actualProduct */
        $actualProduct = $this->repository->find(1);
        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product',
            $actualProduct
        );

        $this->assertEquals($product->title, $actualProduct->title);

        /** @var CategoryObject $category */
        $category = $actualProduct->category;
        $this->assertEquals($category->title, $category->title);

        $this->assertNull($actualProduct->limited);

        $actualProduct->limited = true;
        $manager->persist($actualProduct);
        $manager->commit();

        $actualProduct = $this->repository->find(1);

        $this->assertTrue($actualProduct->limited);
    }

    /**
     * Test if exception is thrown on read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Bulk operation is not permitted.
     */
    public function testPersistReadOnlyManager()
    {
        $manager = $this->getContainer()->get('es.manager.readonly');

        $product = new Product();
        $product->title = 'test';

        $manager->persist($product);
        $manager->commit();
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
        $product->category = $category;

        $out[] = [
            $product,
            'Expected object of type ' .
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject, ' .
            'got ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product.',
        ];

        // Case #1: a single link is set, although field is set to multiple.
        $product = new Product();
        $product->relatedCategories = new CategoryObject();
        $out[] = [$product, "Variable isn't traversable, although field is set to multiple."];

        // Case #2: invalid type of object is set to the field.
        $product = new Product;
        $product->category = new \stdClass();
        $out[] = [
            $product,
            'Expected object of type ' .
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject, got stdClass.',
        ];

        // Case #3: invalid type of object is set in single field.
        $product = new Product;
        $product->category = [new CategoryObject()];
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
        /** @var Manager $manager */
        $manager = $this->repository->getManager();

        $product = new Product();
        $product->setId('testId');
        $product->setTtl(500000);
        $product->setScore('1.0');
        $product->title = 'acme';

        $manager->persist($product);
        $manager->commit();

        $actualProduct = $this->repository->find('testId');

        $this->assertEquals($product->getId(), $actualProduct->getId());
        $this->assertLessThan($product->getTtl(), $actualProduct->getTtl());
    }

    /**
     * Tests if DateTime object is being parsed.
     */
    public function testPersistDateField()
    {
        /** @var Manager $manager */
        $manager = $this->repository->getManager();

        $product = new Product();
        $product->setId('testId');
        $product->released = new \DateTime('2100-01-02 03:04:05.889342');

        $manager->persist($product);
        $manager->commit();

        $actualProduct = $this->repository->find('testId');

        $this->assertGreaterThan(time(), $actualProduct->released->getTimestamp());
    }

    /**
     * Check if `token_count` field works as expected.
     */
    public function testPersistTokenCountField()
    {
        $manager = $this->repository->getManager();
        $product = new Product();
        $product->tokenPiecesCount = 't e s t';
        $manager->persist($product);
        $manager->commit();

        // Analyzer is whitespace, so there are four tokens.
        $search = new Search();
        $search->addQuery(new TermQuery('pieces_count.count', '4'));
        $this->assertEquals(1, $this->repository->execute($search)->count());

        // Test with invalid count.
        $search = new Search();
        $search->addQuery(new TermQuery('pieces_count.count', '6'));
        $this->assertEquals(0, $this->repository->execute($search)->count());
    }

    /**
     * Tests setter and getter of index name.
     */
    public function testIndexName()
    {
        $uniqueIndexName = 'test_index_' . uniqid();

        $manager = $this->repository->getManager();
        $this->assertNotEquals($uniqueIndexName, $manager->getIndexName());

        $manager->setIndexName($uniqueIndexName);
        $this->assertEquals($uniqueIndexName, $manager->getIndexName());
    }

    /**
     * Test for getRepository(). Check if local cache is working.
     */
    public function testGetRepository()
    {
        $manager = $this->repository->getManager();
        $expected = $manager->getRepository('AcmeBarBundle:Product');
        $repository = $manager->getRepository('AcmeBarBundle:Product');

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
        $manager = $this->repository->getManager();
        $manager->getRepository(12345);
    }

    /**
     * Test if execute() works with multiple types.
     */
    public function testExecuteQueryOnMultipleTypes()
    {
        $result = $this->getManager('foo')->execute(
            ['AcmeBarBundle:Product', 'AcmeFooBundle:Customer'],
            new Search()
        );

        $this->assertCount(5, $result);
    }

    /**
     * Test for remove().
     */
    public function testRemove()
    {
        $manager = $this->getManager('foo');

        $product = $manager->find('AcmeBarBundle:Product', 1);
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product', $product);

        $manager->remove($product);
        $manager->commit();

        $product = $manager->find('AcmeBarBundle:Product', 1);
        $this->assertNull($product);
    }

    /**
     * Test for remove() in case document has no annotation for ID field.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage must have @MetaField annotation for "_id"
     */
    public function testRemoveException()
    {
        $this->getManager()->remove(new WithoutId());
    }
}
