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
use ONGR\ElasticsearchBundle\DSL\Filter\MissingFilter;
use ONGR\ElasticsearchBundle\DSL\Filter\PrefixFilter;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\DSL\Query\RangeQuery;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\DSL\Suggester\Completion;
use ONGR\ElasticsearchBundle\DSL\Suggester\Context;
use ONGR\ElasticsearchBundle\DSL\Suggester\Phrase;
use ONGR\ElasticsearchBundle\DSL\Suggester\Term;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Result\IndicesResult;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\SimpleOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\TermOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionIterator;
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
                        'description' => 'goo Lorem',
                        'suggestions' => [
                            'input' => ['Lorem', 'ipsum', 'cons'],
                            'output' => 'Lorem ipsum',
                            'payload' => ['test' => true],
                            'weight' => 1,
                            'context' => [
                                'location' => [0, 0],
                                'price' => 500,
                            ],
                        ],
                        'completion_suggesting' => [
                            'input' => ['Lorem', 'ipsum'],
                            'output' => 'Lorem ipsum',
                            'weight' => 1,
                        ],
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
        $document = $repository->createDocument();

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
     * Data provider for testSuggest().
     *
     * @return array
     */
    public function getSuggestData()
    {
        $out = [];

        // Case #0: simple single term suggester.
        $expectedResults = [
            'description-term' => [
                [
                    'text' => 'distibutd',
                    'offset' => '0',
                    'length' => '9',
                    'options' => [
                        new TermOption('disributed', 0.0, 1),
                        new TermOption('distributed', 0.0, 1),
                    ],
                ],
            ],
        ];

        $suggesters = [new Term('description', 'distibutd')];
        $out[] = ['suggesters' => $suggesters, 'expectedResults' => $expectedResults];

        // Case #1: simple single phrase suggester.
        $expectedResults = [
            'description-phrase' => [
                [
                    'text' => 'Lorm adip',
                    'offset' => '0',
                    'length' => '9',
                    'options' => [new SimpleOption('lorem adip', 0.0)],
                ],
            ],
        ];

        $suggesters = [new Phrase('description', 'Lorm adip')];
        $out[] = ['suggesters' => $suggesters, 'expectedResults' => $expectedResults];

        // Case #2: simple context suggester.
        $geoContext = new Context\GeoContext('location', ['lat' => 0, 'lon' => 0]);
        $categoryContext = new Context\CategoryContext('price', '500');
        $context = new Context('suggestions', 'cons');
        $context->addContext($geoContext);
        $context->addContext($categoryContext);

        $expectedResults = [
            'suggestions-completion' => [
                [
                    'text' => 'cons',
                    'offset' => '0',
                    'length' => '4',
                    'options' => [new CompletionOption('Lorem ipsum', 0.0, ['test' => true])],
                ],
            ],
        ];

        $out[] = ['suggesters' => $context, 'expectedResults' => $expectedResults];

        // Case #3: simple completion suggester.
        $completion = new Completion('completion_suggesting', 'ipsum');
        $expectedResults = [
            'completion_suggesting-completion' => [
                [
                    'text' => 'ipsum',
                    'offset' => '0',
                    'length' => '5',
                    'options' => [new SimpleOption('Lorem ipsum', 0.0, null)],
                ],
            ],
        ];

        $out[] = ['suggesters' => $completion, 'expectedResults' => $expectedResults];

        // Case #4: all together.
        $geoContext = new Context\GeoContext('location', ['lat' => 0, 'lon' => 0]);
        $categoryContext = new Context\CategoryContext('price', '500');
        $context = new Context('suggestions', 'cons');
        $context->addContext($geoContext);
        $context->addContext($categoryContext);
        $suggesters = [
            new Term('description', 'distibutd'),
            new Phrase('description', 'Lorm adip'),
            $context,
            new Completion('completion_suggesting', 'ipsum'),
        ];
        $expectedResults = [
            'description-term' => [
                [
                    'text' => 'distibutd',
                    'offset' => '0',
                    'length' => '9',
                    'options' => [
                        new TermOption('disributed', 0.0, 1),
                        new TermOption('distributed', 0.0, 1),
                    ],
                ],
            ],
            'description-phrase' => [
                [
                    'text' => 'Lorm adip',
                    'offset' => '0',
                    'length' => '9',
                    'options' => [new SimpleOption('lorem adip', 0.0)],
                ],
            ],
            'suggestions-completion' => [
                [
                    'text' => 'cons',
                    'offset' => '0',
                    'length' => '4',
                    'options' => [new CompletionOption('Lorem ipsum', 0.0, ['test' => true])],
                ],
            ],
            'completion_suggesting-completion' => [
                [
                    'text' => 'ipsum',
                    'offset' => '0',
                    'length' => '5',
                    'options' => [new SimpleOption('Lorem ipsum', 0.0, null)],
                ],
            ],
        ];
        $out[] = ['suggesters' => $suggesters, 'expectedResults' => $expectedResults];

        return $out;
    }

    /**
     * Check if suggest api works as expected.
     *
     * @param array $suggesters
     * @param array $expectedResults
     *
     * @dataProvider getSuggestData()
     */
    public function testSuggest($suggesters, $expectedResults)
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeTestBundle:Product');

        $results = $repository->suggest($suggesters);
        $this->assertScore($results);

        $this->assertSameSize($expectedResults, $results);
        foreach ($expectedResults as $name => $expectedSuggestion) {
            foreach ($expectedResults[$name] as $key => $entry) {
                $this->assertEquals($entry['text'], $results[$name][$key]->getText());
                $this->assertEquals($entry['offset'], $results[$name][$key]->getOffset());
                $this->assertEquals($entry['length'], $results[$name][$key]->getLength());
                $this->assertEquals($entry['options'], iterator_to_array($results[$name][$key]->getOptions()));
            }
        }
    }

    /**
     * Tests if repository is fetched without suffix.
     */
    public function testGetRepositoryWithDoucmentSuffix()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeTestBundle:Color');

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\ColorDocument',
            $repository->createDocument()
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
        $this->assertNotInstanceOf('ONGR\ElasticsearchBundle\DSL\Bool\Bool', $builder, 'Query should not be bool.');
        $this->assertInstanceOf('ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery', $builder, 'Query should be same.');
    }

    /**
     * Assert suggestion score is set.
     *
     * @param SuggestionIterator $suggestions
     */
    private function assertScore(SuggestionIterator $suggestions)
    {
        foreach ($suggestions as $suggestion) {
            foreach ($suggestion as $suggestionEntry) {
                foreach ($suggestionEntry->getOptions() as $option) {
                    $this->assertTrue($option->getScore() > 0.0);
                    $option->setScore(0.0);
                }
            }
        }
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
