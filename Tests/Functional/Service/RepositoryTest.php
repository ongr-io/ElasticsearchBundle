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

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class RepositoryTest extends AbstractElasticsearchTestCase
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
                        'description' => 'goo Lorem',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 1000,
                        'description' => 'foo bar Lorem adips distributed disributed',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'gar',
                        'price' => 100,
                        'description' => 'foo bar Loremo',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'tuna',
                        'description' => 'tuna bar Loremo Batman',
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
        $out[] = [
            [1],
            ['title' => 'foo'],
        ];

        // Case #1 find by multiple titles.
        $out[] = [
            [1, 2],
            [
                'title' => [
                    'foo',
                    'bar',
                ],
            ],
        ];

        // Case #2 find by multiple titles and simple sort.
        $out[] = [
            [2, 1],
            [
                'title' => [
                    'foo',
                    'bar',
                ],
            ],
            ['title.raw' => FieldSort::ASC],
        ];

        // Case #3 find by multiple titles and multiple sorts.
        $criteria = [
            'description' => [
                'foo',
                'goo',
            ],
            'title' => [
                'foo',
                'bar',
                'gar',
            ],
        ];
        $out[] = [
            [2, 1, 3],
            $criteria,
            [
                'title.raw' => FieldSort::ASC,
                'price' => FieldSort::DESC,
            ],
        ];

        // Case #4 offset.
        $out[] = [
            [3, 1],
            $criteria,
            [
                'price' => FieldSort::DESC,
            ],
            null,
            1,
        ];

        // Case #5 limit.
        $out[] = [
            [1, 3],
            $criteria,
            [
                'price' => FieldSort::ASC,
            ],
            2,
        ];

        // Case #6 limit and offset.
        $out[] = [
            [1],
            $criteria,
            [
                'title.raw' => FieldSort::ASC,
                'price' => FieldSort::DESC,
            ],
            1,
            1,
        ];

        // Case #7 find when title not equal foo.
        $out[] = [
            [2, 3, 4],
            ['!title' => 'foo'],
        ];

        // Case #8 find when title not equal foo or title not equal bar.
        $out[] = [
            [3, 4],
            [
                '!title' => [
                    'foo',
                    'bar',
                ],
            ],
        ];

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
        $repo = $this->getManager()->getRepository('TestBundle:Product');

        $fullResults = $repo->findBy($criteria, $orderBy, $limit, $offset);

        $results = [];

        foreach ($fullResults as $result) {
            $results[] = (int) $result->getId();
        }

        // Results are not sorted, they will be returned in random order.
        if (empty($orderBy)) {
            sort($results);
            sort($expectedResults);
        }

        $this->assertEquals($expectedResults, $results);
    }

    /**
     * Data provider for test find one by.
     *
     * @return array
     */
    public function getFindOneByData()
    {
        $out = [];

        // Case #0 find one by title for not existed.
        $out[] = [
            null,
            ['title' => 'baz'],
        ];

        // Case #1 simple find one by title.
        $out[] = [
            1,
            ['title' => 'foo'],
        ];

        // Case #2 find one by multiple titles and simple sort.
        $out[] = [
            2,
            [
                'title' => [
                    'foo',
                    'bar',
                ],
            ],
            ['title.raw' => FieldSort::ASC],
        ];

        // Case #3 find one by multiple titles and multiple sorts.
        $criteria = [
            'description' => [
                'foo',
                'goo',
            ],
            'title' => [
                'foo',
                'bar',
                'gar',
            ],
        ];
        $out[] = [
            2,
            $criteria,
            [
                'price' => FieldSort::DESC,
            ],
        ];

        return $out;
    }

    /**
     * Check if find one by works as expected.
     *
     * @param int|null $expectedResult
     * @param array    $criteria
     * @param array    $orderBy
     *
     * @dataProvider getFindOneByData()
     */
    public function testFindOneBy($expectedResult, $criteria, $orderBy = [])
    {
        $repo = $this->getManager()->getRepository('TestBundle:Product');

        $result = $repo->findOneBy($criteria, $orderBy);

        if ($expectedResult === null) {
            $this->assertNull($result);
        } else {
            $this->assertNotNull($result);
            $this->assertEquals($expectedResult, $result->getId());
        }
    }

    /**
     * Test repository find method with array result type.
     */
    public function testFind()
    {
        $manager = $this->getManager();

        $product = new Product;
        $product->setId('123');
        $product->setTitle('foo');

        $manager->persist($product);
        $manager->commit();

        $repo = $manager->getRepository('TestBundle:Product');

        $result = $repo->find(123);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product', $result);
        $this->assertEquals($product->getId(), $result->getId());
    }

    /**
     * Test repository find on non-existent document.
     */
    public function testFindNull()
    {
        $repo = $this->getManager()->getRepository('TestBundle:Product');

        $this->assertNull($repo->find(123));
    }

    /**
     * Tests remove method.
     */
    public function testRemove()
    {
        $manager = $this->getManager();

        $repo = $manager->getRepository('TestBundle:Product');

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

        $repo = $manager->getRepository('TestBundle:Product');

        $repo->remove(500);
    }

    /**
     * Test parseResult when 0 documents found using execute.
     */
    public function testRepositoryExecuteWhenZeroResult()
    {
        $repository = $this->getManager()->getRepository('TestBundle:Product');

        $search = $repository
            ->createSearch()
            ->addQuery(new PrefixQuery('title', 'dummy'), BoolQuery::FILTER);

        $searchResult = $repository->findDocuments($search);
        $this->assertInstanceOf(
            '\ONGR\ElasticsearchBundle\Result\DocumentIterator',
            $searchResult
        );
        $this->assertCount(0, $searchResult);
    }

    /**
     * Tests if document is being updated when persisted.
     */
    public function testDocumentUpdate()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');

        $document = new Product;

        $document->setId(5);
        $document->setTitle('acme');

        $manager->persist($document);
        $manager->commit();

        // Creates document.
        /** @var Product $document */
        $document = $repository->find(5);
        $this->assertEquals(
            [
                'id' => '5',
                'title' => 'acme',
            ],
            [
                'id' => $document->getId(),
                'title' => $document->getTitle(),
            ],
            'Document should be created.'
        );

        $document->setTitle('acme bar');

        // Updates document.
        $manager->persist($document);
        $manager->commit();

        $document = $repository->find(5);
        $this->assertEquals(
            [
                'id' => '5',
                'title' => 'acme bar',
            ],
            [
                'id' => $document->getId(),
                'title' => $document->getTitle(),
            ],
            'Document should be updated.'
        );
    }

    /**
     * Tests if repository returns same manager as it was original.
     */
    public function testGetManager()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');
        $this->assertSame($manager, $repository->getManager());
    }

    /**
     * Tests if document can be updated with partial update without initiating document object.
     */
    public function testPartialUpdate()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');

        /** @var Product $product */
        $product = $repository->find(1);
        $this->assertEquals($product->getTitle(), 'foo');

        $repository->update(1, ['title' => 'acme']);

        $product = $repository->find(1);
        $this->assertEquals($product->getTitle(), 'acme');
    }

    /**
     * Tests if document can be updated with partial update without initiating document object.
     */
    public function testPartialUpdateWithDocumentResponse()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');

        $result = $repository->update(1, ['title' => 'acme'], null, ['fields' => 'id,title,price']);

        $this->assertEquals(1, $result['_id']);
        $this->assertEquals('acme', $result['get']['fields']['title'][0]);
        $this->assertEquals(10, $result['get']['fields']['price'][0]);

        $this->assertNotContains('id', $result['get']['fields']);
    }

    /**
     * Tests results counting via search query.
     */
    public function testCountApi()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');

        $matchAll = new MatchAllQuery();
        $search = $repository->createSearch();
        $search->addQuery($matchAll);

        $count = $repository->count($search);

        $this->assertEquals(4, $count);
    }

    /**
     * Tests results counting raw response from client.
     */
    public function testCountApiRawResponse()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');

        $matchAll = new MatchAllQuery();
        $search = $repository->createSearch();
        $search->addQuery($matchAll);

        $count = $repository->count($search, [], true);

        $this->assertTrue(is_array($count));
        $this->assertEquals(4, $count['count']);

        $shards = [
            'total' => 5,
            'successful' => 5,
            'failed' => 0,
        ];

        $this->assertEquals($shards['total'], $count['_shards']['total']);
        $this->assertEquals($shards['successful'], $count['_shards']['successful']);
        $this->assertEquals($shards['failed'], $count['_shards']['failed']);

        #TODO remove if statement after bundle drops sf 3.0 support
        if (isset($count['_shards']['skipped'])) {
            $this->assertEquals(0, $count['_shards']['skipped']);
        }
    }

    /**
     * Tests mget method
     */
    public function testFindByIds()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('TestBundle:Product');
        $results = $repository->findByIds([1, 2, 5]);

        $this->assertInstanceOf(DocumentIterator::class, $results);
        $this->assertEquals(2, count($results));
        $i = 0;
        foreach ($results as $product) {
            $this->assertInstanceOf(Product::class, $product);
            $this->assertEquals(
                $this->getDataArray()['default']['product'][$i++]['price'],
                $product->getPrice()
            );
        }
    }
}
