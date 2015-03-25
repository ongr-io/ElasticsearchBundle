<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL;

use ONGR\ElasticsearchBundle\DSL\Filter\IdsFilter;
use ONGR\ElasticsearchBundle\DSL\Filter\PrefixFilter;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\DSL\Query\RangeQuery;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Sort\Sort;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Search functional test.
 */
class SearchTest extends ElasticsearchTestCase
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @return array
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
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'foo bar',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'foo bar baz',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'foobar',
                        'price' => 10000,
                        'description' => 'foo bar baz foobar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getProductsArray()
    {
        $data = $this->getDataArray()['default']['product'];

        foreach ($data as &$record) {
            unset($record['_id']);
        }

        return $data;
    }

    /**
     * Gets results array for specified Search.
     *
     * @param \ONGR\ElasticsearchBundle\DSL\Search $search
     *
     * @return array|\ONGR\ElasticsearchBundle\Result\DocumentIterator
     */
    protected function getSearchResultsArray($search)
    {
        return $this->repository->execute($search, Repository::RESULTS_ARRAY);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
    }

    /**
     * Test Search API 'from' and 'size' properties.
     */
    public function testSearchFromSizeExplain()
    {
        $testQuery = new MatchAllQuery();
        $search = $this->repository->createSearch()
            ->addQuery($testQuery)
            ->addSort(new Sort('price', Sort::ORDER_DESC))
            ->setFrom(2)
            ->setSize(2)
            ->setExplain(false);

        $results = $this->getSearchResultsArray($search);

        $expected = array_slice(array_reverse($this->getProductsArray()), 2, 2);

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }

    /**
     * Test Search API 'source' property.
     */
    public function testSearchSource()
    {
        $testQuery = new TermQuery('_id', '1');
        $search = $this->repository->createSearch()
            ->addQuery($testQuery)
            ->setSource(['_id', 'title', 'price']);

        $expected = [$this->getProductsArray()[0]];
        unset($expected[0]['description']);

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Test Search API 'fields' property.
     */
    public function testSearchFields()
    {
        $testQuery = new TermQuery('_id', '1');
        $search = $this->repository->createSearch()
            ->addQuery($testQuery)
            ->setFields(['title']);

        $expected = [$this->getProductsArray()[0]];
        unset($expected[0]['price']);
        unset($expected[0]['description']);

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Test Search API 'script_fields property.
     */
    public function testSearchScriptFields()
    {
        $search = $this->repository->createSearch()
            ->addQuery(new TermQuery('_id', '1'))
            ->setScriptFields(new \StdClass());

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Test Search API 'post_filter' property.
     */
    public function testSearchPostFilter()
    {
        $postFilter = new IdsFilter(['1']);

        $search = $this->repository->createSearch()
            ->addQuery(new MatchAllQuery())
            ->addPostFilter($postFilter);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Check if search api works as expected with multiple post filters.
     */
    public function testSearchPostFilterMultiple()
    {
        $postFilter1 = new IdsFilter(['1']);
        $postFilter2 = new IdsFilter(['3']);

        $search = $this->repository->createSearch()
            ->addQuery(new MatchAllQuery())
            ->addPostFilter($postFilter1, 'should')
            ->addPostFilter($postFilter2, 'should');

        $results = $this->getSearchResultsArray($search);

        $expected = [
            $this->getProductsArray()[0],
            $this->getProductsArray()[2],
        ];

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }

    /**
     * Test Search API 'search_type' property.
     */
    public function testSearchType()
    {
        $search = $this->repository->createSearch()
            ->addQuery(new TermQuery('_id', '1'))
            ->setSearchType('query_and_fetch');

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Test 'scroll' functionality.
     */
    public function testSearchScroll()
    {
        $search = $this->repository->createSearch();
        $search->setSize(2);
        $search->setScroll('1m');
        $search->addQuery(new MatchAllQuery());

        $results = $this->repository->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('_scroll_id', $results);
    }

    /**
     * Data provider for testSearchPreference().
     *
     * @return array[]
     */
    public function getSearchPreferenceData()
    {
        // Case #0 parameter as string.
        $out[] = [
            '_local',
            ['preference' => '_local'],
        ];

        // Case #1 parameter as array.
        $out[] = [
            [
                '_shards:2,3',
                '_primary',
            ],
            ['preference' => '_shards:2,3;_primary'],
        ];

        return $out;
    }

    /**
     * Tests search preference.
     *
     * @param mixed $params
     * @param array $expected
     *
     * @dataProvider getSearchPreferenceData()
     */
    public function testSearchPreference($params, $expected)
    {
        $search = $this->repository->createSearch()
            ->setPreference($params);

        $results = $search->getQueryParams();

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests query manipulation.
     */
    public function testQueryManipulation()
    {
        $search = $this->repository->createSearch();
        $search->addQuery(new RangeQuery('price', ['gte' => 200, 'lte' => 2000]));
        $search->addFilter(new PrefixFilter('title', 'ba'));

        $search->setBoolQueryParameters(['boost' => 1]);

        $expected = [$this->getProductsArray()[2]];

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Tests prefix filter and ids filter with cache.
     */
    public function testPrefixFilterAndIdsFilterWithCache()
    {
        $search = $this->repository->createSearch();

        $search->addFilter(new PrefixFilter('title', 'foo'));
        $search->addFilter(new IdsFilter(['1', '2']), 'should');
        $search->setBoolFilterParameters(['_cache' => true]);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }

    /**
     * Tests prefix filter and ids filter without cache.
     */
    public function testPrefixFilterAndIdsFilterWithoutCache()
    {
        $search = $this->repository->createSearch();

        $search->addFilter(new PrefixFilter('title', 'foo'));
        $search->addFilter(new IdsFilter(['1', '2']), 'must');
        $search->setBoolFilterParameters(['_cache' => false]);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $this->getSearchResultsArray($search));
    }
}
