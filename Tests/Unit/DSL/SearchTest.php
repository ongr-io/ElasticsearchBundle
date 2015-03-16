<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL;

use Elasticsearch\Endpoints\Indices\Validate\Query;
use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\Test\EncapsulationTestAwareTrait;

/**
 * Test for SearchTest.
 */
class SearchTest extends \PHPUnit_Framework_TestCase
{
    use EncapsulationTestAwareTrait;

    /**
     * Returns list of fields to test. Works as data provider.
     *
     * @return array
     */
    public function getFieldsData()
    {
        return [
            ['filters'],
            ['postFilters'],
            ['boolFilterParameters'],
            ['size'],
            ['from'],
            ['sorts'],
            ['minScore'],
            ['fields', 'array'],
            ['scroll'],
            ['source'],
            ['scriptFields'],
            ['suggesters'],
            ['highlight'],
            ['searchType'],
            ['explain', 'boolean'],
            ['stats'],
            ['aggregations'],
        ];
    }

    /**
     * Returns entity class name.
     *
     * @return string
     */
    public function getClassName()
    {
        $this->setStub(new Search());
        $this->setIgnoredFields(
            [
                'query',
                'boolQueryParams',
                'scrollDuration',
                'fields',
                'preference',
                'boolQueryParameters',
            ]
        );

        return 'ONGR\ElasticsearchBundle\DSL\Search';
    }

    /**
     * Test add, get and destroy query methods.
     */
    public function testAddAndDestroyQuery()
    {
        $search = new Search();
        $queryMock = $this->getMock('ONGR\ElasticsearchBundle\DSL\Query\Query');

        $this->assertEmpty($search->getQueries());
        $search->addQuery($queryMock);
        $this->assertNotNull($search->getQueries());

        $search->destroyQuery();

        $this->assertEmpty($search->getQueries());
    }

    /**
     * Test getScroll method.
     */
    public function testGetScrollDuration()
    {
        $search = new Search();
        $search->setScroll('10');
        $this->assertEquals(10, $search->getScrollDuration());
    }
}
