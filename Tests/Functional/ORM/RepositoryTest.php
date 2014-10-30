<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\DSL\Filter\PrefixFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product;

class RepositoryTest extends ElasticsearchTestCase
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
                        'price' => 10,
                        'description' => 'goo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 1000,
                        'description' => 'foo bar',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'gar',
                        'price' => 100,
                        'description' => 'foo bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for test find by.
     *
     * @return array
     */
    public function getFindByData()
    {
        $out = [];

        // Case #0 simple find by title.
        $out[] = [[1], ['title' => 'foo']];

        // Case #1 find by multiple titles.
        $out[] = [[1, 2], ['title' => ['foo', 'bar']]];

        // Case #2 find by multiple titles and simple sort.
        $out[] = [[2, 1], ['title' => ['foo', 'bar']], ['title' => 'asc']];

        // Case #3 find by multiple titles and multiple sorts.
        $criteria = [
            'description' => ['foo', 'goo'],
            'title' => ['foo', 'bar', 'gar'],
        ];
        $out[] = [[2, 3, 1], $criteria, ['description' => 'ASC', 'price' => 'DESC']];

        // Case #4 offset.
        $out[] = [[3, 1], $criteria, ['description' => 'ASC', 'price' => 'DESC'], null, 1];

        // Case #5 limit.
        $out[] = [[2, 3], $criteria, ['description' => 'ASC', 'price' => 'DESC'], 2];

        // Case #6 limit and offset.
        $out[] = [[3], $criteria, ['description' => 'ASC', 'price' => 'DESC'], 1, 1];

        return $out;
    }

    /**
     * Check if find by works as expected.
     *
     * @param array $expectedResults
     * @param array $criteria
     * @param array $orderBy
     * @param int   $limit
     * @param int   $offset
     *
     * @dataProvider getFindByData()
     */
    public function testFindBy($expectedResults, $criteria, $orderBy = [], $limit = null, $offset = null)
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $fullResults = $repo->findBy($criteria, $orderBy, $limit, $offset);

        $results = [];

        /** @var DocumentInterface $result */
        foreach ($fullResults as $result) {
            $results[] = $result->getId();
        }

        // Results are not sorted, they will be returned in random order.
        if (empty($orderBy)) {
            sort($results);
            sort($expectedResults);
        }

        $this->assertEquals($expectedResults, $results);
    }

    /**
     * Test repository find method with array result type.
     */
    public function testFind()
    {
        $manager = $this->getManager();

        $product = new Product();
        $product->setId(123);
        $product->title = 'foo';

        $manager->persist($product);
        $manager->commit();

        $repo = $manager->getRepository('AcmeTestBundle:Product');

        $result = $repo->find(123);

        $this->assertEquals($product, $result);
    }

    /**
     * Test repository find on non-existent document.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Missing404Exception
     */
    public function testFindException()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $repo->find(123);
    }

    /**
     * Test repository find method in repo with many types.
     *
     * @expectedException \LogicException
     */
    public function testFindInMultiTypeRepo()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository(['AcmeTestBundle:Product', 'AcmeTestBundle:Content']);

        $repo->find(1);
    }

    /**
     * Tests remove method.
     */
    public function testRemove()
    {
        $manager = $this->getManager();

        $repo = $manager->getRepository('AcmeTestBundle:Product');

        $response = $repo->remove(3);

        $this->assertArrayHasKey('found', $response);
        $this->assertEquals(1, $response['found']);
    }

    /**
     * Tests remove method 404 exception.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Missing404Exception
     */
    public function testRemoveException()
    {
        $manager = $this->getManager();

        $repo = $manager->getRepository('AcmeTestBundle:Product');

        $repo->remove(500);
    }

    /**
     * Test for createDocument().
     */
    public function testCreateDocument()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $document = $repo->createDocument();

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product',
            $document
        );
    }

    /**
     * Test for createDocument() in case multiple namespaces are associated with repository.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage can not create new document
     */
    public function testCreateDocumentException()
    {
        $repo = $this->getManager()->getRepository(['AcmeTestBundle:Product', 'AcmeTestBundle:Content']);
        $repo->createDocument();
    }

    /**
     * Test parseResult when 0 documents found using execute.
     */
    public function testRepositoryExecuteWhenZeroResult()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch();
        $search->addFilter(new PrefixFilter('title', 'dummy'));

        $this->assertInstanceOf(
            '\ONGR\ElasticsearchBundle\Result\DocumentIterator',
            $repo->execute($search, Repository::RESULTS_OBJECT)
        );
        $this->assertCount(0, $repo->execute($search, Repository::RESULTS_OBJECT));
    }

    /**
     * @return array
     */
    protected function getProductsArray()
    {
        return $this->getDataArray()['default']['product'];
    }
}
