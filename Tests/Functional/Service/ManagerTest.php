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

use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\WithoutId;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
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

        /** @var Product $actualProduct */
        $actualProduct = $manager->find('AcmeBarBundle:Product', 1);
        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product',
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

        $actualProduct = $manager->find('AcmeBarBundle:Product', 1);

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
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject, ' .
            'got ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product.',
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
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\CategoryObject, got stdClass.',
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
        $product->setTtl(500000);
        $product->setTitle('acme');

        $manager->persist($product);
        $manager->commit();

        $actualProduct = $manager->find('AcmeBarBundle:Product', 'testId');

        $this->assertEquals($product->getId(), $actualProduct->getId());
        $this->assertLessThan($product->getTtl(), $actualProduct->getTtl());
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

        $actualProduct = $manager->find('AcmeBarBundle:Product', 'testId');

        $this->assertGreaterThan(time(), $actualProduct->getReleased()->getTimestamp());
    }

    /**
     * Check if `token_count` field works as expected.
     */
    public function testPersistTokenCountField()
    {
        $manager = $this->getManager();
        $product = new Product();
        $product->setTokenPiecesCount('t e s t');
        $manager->persist($product);
        $manager->commit();

        // Analyzer is whitespace, so there are four tokens.
        $search = new Search();
        $search->addQuery(new TermQuery('pieces_count.count', '4'));
        $this->assertEquals(1, $manager->execute(['AcmeBarBundle:Product'], $search)->count());

        // Test with invalid count.
        $search = new Search();
        $search->addQuery(new TermQuery('pieces_count.count', '6'));
        $this->assertEquals(0, $manager->execute(['AcmeBarBundle:Product'], $search)->count());
    }

    /**
     * Tests setter and getter of index name.
     */
    public function testIndexName()
    {
        $uniqueIndexName = 'test_index_' . uniqid();

        $manager = $this->getManager();
        $this->assertNotEquals($uniqueIndexName, $manager->getIndexName());

        $manager->setIndexName($uniqueIndexName);
        $this->assertEquals($uniqueIndexName, $manager->getIndexName());
    }

    /**
     * Test for getRepository(). Check if local cache is working.
     */
    public function testGetRepository()
    {
        $manager = $this->getManager();
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
        $manager = $this->getManager();
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
     * @expectedExceptionMessage must have property with @Id annotation
     */
    public function testRemoveException()
    {
        $this->getManager()->remove(new WithoutId());
    }

    /**
     * Tests testParseResults method with different result types
     */
    public function testParseResultsWithDifferentResultTypes()
    {
        $fooManager = $this->getManager('foo');
        $defaultManager = $this->getManager();

        $repo = $fooManager->getRepository('AcmeBarBundle:Product');
        $search = $repo->createSearch();
        $search->addQuery(new MatchAllQuery());
        $products = $repo->execute($search, 'array');
        $this->assertArrayHasKey(0, $products);

        $repo = $defaultManager->getRepository('AcmeBarBundle:Product');
        $search = $repo->createSearch();
        $search->addQuery(new MatchAllQuery());
        $products = $repo->execute($search, 'array');
        $this->assertArrayNotHasKey(0, $products);

        $repo = $fooManager->getRepository('AcmeBarBundle:Product');
        $search = $repo->createSearch();
        $search->addQuery(new MatchAllQuery());
        $products = $repo->execute($search, 'raw_iterator');
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\RawIterator', $products);
    }

    /**
     * Tests exception that is thrown by parseResults()
     * when a bad result type is provided
     *
     * @expectedException \Exception
     */
    public function testParseResultsException()
    {
        $manager = $this->getManager();
        $repo = $manager->getRepository('AcmeBarBundle:Product');
        $search = $repo->createSearch();
        $search->addQuery(new MatchAllQuery());
        $repo->execute($search, 'non_existant_type');
    }

    /**
     * Tests the exception thrown by the commit method
     *
     * @expectedException \Elasticsearch\Common\Exceptions\ClientErrorResponseException
     */
    public function testCommitException()
    {
        $manager = $this->getManager();
        $product = new Product();
        $product->setTitle(new Product());

        $manager->persist($product);
        $manager->commit();
    }
}
