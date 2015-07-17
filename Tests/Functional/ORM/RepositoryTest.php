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
use ONGR\ElasticsearchBundle\Result\IndicesResult;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product;
use ONGR\ElasticsearchDSL\Filter\PrefixFilter;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;

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
                'color' => [
                    [
                        '_id' => 1,
                        'enabled_cdn' => [
                            [
                                'cdn_url' => 'foo',
                            ],
                        ],
                        'disabled_cdn' => [
                            [
                                'cdn_url' => 'foo',
                            ],
                        ],
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
            ['title' => 'asc'],
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
            [2, 3, 1],
            $criteria,
            [
                'description' => 'ASC',
                'price' => 'DESC',
            ],
        ];

        // Case #4 offset.
        $out[] = [
            [3, 1],
            $criteria,
            [
                'description' => 'ASC',
                'price' => 'DESC',
            ],
            null,
            1,
        ];

        // Case #5 limit.
        $out[] = [
            [2, 3],
            $criteria,
            [
                'description' => 'ASC',
                'price' => 'DESC',
            ],
            2,
        ];

        // Case #6 limit and offset.
        $out[] = [
            [3],
            $criteria,
            [
                'description' => 'ASC',
                'price' => 'DESC',
            ],
            1,
            1,
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
            ['title' => 'asc'],
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
                'description' => 'ASC',
                'price' => 'DESC',
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
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

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

        $product = new Product();
        $product->setId('123');
        $product->title = 'foo';

        $manager->persist($product);
        $manager->commit();

        $repo = $manager->getRepository('AcmeTestBundle:Product');

        $result = $repo->find(123);

        $this->assertEquals(get_object_vars($product), get_object_vars($result));
    }

    /**
     * Test repository find on non-existent document.
     */
    public function testFindNull()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $this->assertNull($repo->find(123));
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
     * Test parseResult when 0 documents found using execute.
     */
    public function testRepositoryExecuteWhenZeroResult()
    {
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repository
            ->createSearch()
            ->addFilter(new PrefixFilter('title', 'dummy'));

        $searchResult = $repository->execute($search, Repository::RESULTS_OBJECT);
        $this->assertInstanceOf(
            '\ONGR\ElasticsearchBundle\Result\DocumentIterator',
            $searchResult
        );
        $this->assertCount(0, $searchResult);
    }

    /**
     * @return array
     */
    protected function getProductsArray()
    {
        return $this->getDataArray()['default']['product'];
    }

    /**
     * Tests if document is being updated when persisted.
     */
    public function testDocumentUpdate()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeTestBundle:Product');

        /** @var Product $document */
        $document = new Product();

        $document->setId(5);
        $document->title = 'awesome';

        $manager->persist($document);
        $manager->commit();

        // Creates document.
        $document = $repository->find(5);
        $this->assertEquals(
            [
                'id' => '5',
                'title' => 'awesome',
            ],
            [
                'id' => $document->getId(),
                'title' => $document->title,
            ],
            'Document should be created.'
        );

        $document->title = 'more awesome';

        // Updates document.
        $manager->persist($document);
        $manager->commit();

        $document = $repository->find(5);
        $this->assertEquals(
            [
                'id' => '5',
                'title' => 'more awesome',
            ],
            [
                'id' => $document->getId(),
                'title' => $document->title,
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
        $repository = $manager->getRepository('AcmeTestBundle:Color');
        $this->assertEquals($manager, $repository->getManager());
    }

    /**
     * Tests if search does not add queries if these was none after execution.
     */
    public function testSameSearchExecution()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeTestBundle:Product');
        $matchAllQuery = new MatchAllQuery();
        $search = $repository
            ->createSearch()
            ->addQuery($matchAllQuery);

        $repository->execute($search);
        $builder = $search->getQuery();
        $this->assertNotInstanceOf('ONGR\ElasticsearchDSL\Query\BoolQuery', $builder, 'Query should not be bool.');
        $this->assertInstanceOf('ONGR\ElasticsearchDSL\Query\MatchAllQuery', $builder, 'Query should be same.');
    }

    /**
     * Tests if documents are deleted by query.
     */
    public function testDeleteByQuery()
    {
        /** @var Manager $manager */
        $all = new MatchAllQuery();
        $manager = $this->getManager();
        $index = $manager->getConnection()->getIndexName();
        $repository = $manager->getRepository('AcmeTestBundle:Product');
        $search = $repository->createSearch()->addQuery($all);
        $results = $repository->execute($search)->count();
        $this->assertEquals(4, $results);

        $query = $repository->createSearch();
        $term = new RangeQuery('price', ['gt' => 1, 'lt' => 200]);
        $query->addQuery($term);

        $expectedResults = [
            'failed' => [$index => 0],
            'successful' => [$index => 5],
            'total' => [$index => 5],
        ];
        /** @var IndicesResult $result */
        $result = $repository->deleteByQuery($query);
        $this->assertEquals($expectedResults['failed'], $result->getFailed());
        $this->assertEquals($expectedResults['successful'], $result->getSuccessful());
        $this->assertEquals($expectedResults['total'], $result->getTotal());

        $search = $repository->createSearch()->addQuery($all);
        $results = $repository->execute($search)->count();
        $this->assertEquals(2, $results);
    }

    /**
     * Tests finding object with enabled property set to false.
     */
    public function testFindWithDisabledProperty()
    {
        $repository = $this
            ->getManager()
            ->getRepository('AcmeTestBundle:Color');

        $search = $repository
            ->createSearch()
            ->addQuery(new TermQuery('disabled_cdn.cdn_url', 'foo'));

        $this->assertCount(0, $repository->execute($search));

        $search = $repository
            ->createSearch()
            ->addQuery(new TermQuery('enabled_cdn.cdn_url', 'foo'));

        $this->assertCount(1, $repository->execute($search));
    }

    /**
     * Tests if find works as expected with RESULTS_RAW return type.
     */
    public function testFindArrayRaw()
    {
        $manager = $this->getManager();
        $index = $manager->getConnection()->getIndexName();
        $repository = $manager->getRepository('AcmeTestBundle:Color');
        $document = $repository->find(1, Repository::RESULTS_RAW);
        $expected = [
            '_index' => $index,
            '_type' => 'color',
            '_id' => 1,
            '_version' => 1,
            'found' => 1,
            '_source' => [
                'enabled_cdn' => [
                    [
                        'cdn_url' => 'foo',
                    ],
                ],
                'disabled_cdn' => [
                    [
                        'cdn_url' => 'foo',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $document);
    }

    /**
     * Tests if find works as expected with RESULTS_ARRAY return type.
     */
    public function testFindArray()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeTestBundle:Color');
        $document = $repository->find(1, Repository::RESULTS_ARRAY);
        $expected = [
            'enabled_cdn' => [
                [
                    'cdn_url' => 'foo',
                ],
            ],
            'disabled_cdn' => [
                [
                    'cdn_url' => 'foo',
                ],
            ],
        ];
        $this->assertEquals($expected, $document);
    }

    /**
     * Tests if find works as expected with RESULTS_RAW_ITERATOR return type.
     */
    public function testFindArrayIterator()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeTestBundle:Product');
        $document = $repository->find(1, Repository::RESULTS_RAW_ITERATOR);
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\RawResultIterator', $document);
    }
}
