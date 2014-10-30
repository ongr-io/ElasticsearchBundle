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

class SearchTest extends ElasticsearchTestCase
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
     * Test Search API 'from' and 'size' properties.
     */
    public function testSearchFromSizeExplain()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $testQuery = new MatchAllQuery();
        $search = $repo->createSearch()
            ->addQuery($testQuery)
            ->addSort(new Sort('price', Sort::ORDER_DESC))
            ->setFrom(2)
            ->setSize(2)
            ->setExplain(false);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

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
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $testQuery = new TermQuery('_id', '1');
        $search = $repo->createSearch()
            ->addQuery($testQuery)
            ->setSource(['_id', 'title', 'price']);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0]];
        unset($expected[0]['description']);

        $this->assertEquals($expected, $results);
    }

    /**
     * Test Search API 'fields' property.
     */
    public function testSearchFields()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $testQuery = new TermQuery('_id', '1');
        $search = $repo->createSearch()
            ->addQuery($testQuery)
            ->setFields(['title']);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0]];
        unset($expected[0]['price']);
        unset($expected[0]['description']);

        $this->assertEquals($expected, $results);
    }

    /**
     * Test Search API 'script_fields property.
     */
    public function testSearchScriptFields()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()
            ->addQuery(new TermQuery('_id', '1'))
            ->setScriptFields(new \StdClass());

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $results);
    }

    /**
     * Test Search API 'post_filter' property.
     */
    public function testSearchPostFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $postFilter = new IdsFilter(['1']);

        $search = $repo->createSearch()
            ->addQuery(new MatchAllQuery())
            ->addPostFilter($postFilter);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $results);
    }

    /**
     * Check if search api works as expected with multiple post filters.
     */
    public function testSearchPostFilterMultiple()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $postFilter1 = new IdsFilter(['1']);
        $postFilter2 = new IdsFilter(['3']);


        $search = $repo->createSearch()
            ->addQuery(new MatchAllQuery())
            ->addPostFilter($postFilter1, 'should')
            ->addPostFilter($postFilter2, 'should');

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0], $this->getProductsArray()[2]];

        sort($results);
        sort($expected);

        $this->assertEquals($expected, $results);
    }

    /**
     * Test Search API 'search_type' property.
     */
    public function testSearchType()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()
            ->addQuery(new TermQuery('_id', '1'))
            ->setSearchType('query_and_fetch');

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $results);
    }

    /**
     * Test 'scroll' functionality.
     */
    public function testSearchScroll()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch();
        $search->setSize(2);
        $search->setScroll('1m');
        $search->addQuery(new MatchAllQuery());

        $results = $repo->execute($search, Repository::RESULTS_RAW);

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
            ['_shards:2,3', '_primary'],
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
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch()
            ->setPreference($params);

        $results = $search->getQueryParams();

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests query manipulation.
     */
    public function testQueryManipulation()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $search = $repo->createSearch();
        $search->addQuery(new RangeQuery('price', ['gte' => 200, 'lte' => 2000]));
        $search->addFilter(new PrefixFilter('title', 'ba'));

        $search->setBoolQueryParameters(['boost' => 1]);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expected = [$this->getProductsArray()[2]];

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests prefix filter and ids filter with cache.
     */
    public function testPrefixFilterAndIdsFilterWithCache()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch();

        $search->addFilter(new PrefixFilter('title', 'foo'));
        $search->addFilter(new IdsFilter(['1', '2']), 'should');

        $search->setBoolFilterParameters(['_cache' => false]);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests prefix filter and ids filter without cache.
     */
    public function testPrefixFilterAndIdsFilterWithoutCache()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repo->createSearch();

        $search->addFilter(new PrefixFilter('title', 'foo'));
        $search->addFilter(new IdsFilter(['1', '2']), 'must');
        $search->setBoolFilterParameters(['_cache' => true]);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [$this->getProductsArray()[0]];

        $this->assertEquals($expected, $results);
    }
}
