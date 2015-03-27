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
            ['boolFilterParameters'],
            ['size'],
            ['from'],
            ['minScore'],
            ['fields', 'array'],
            ['scroll'],
            ['source'],
            ['scriptFields'],
            ['highlight'],
            ['searchType'],
            ['explain', 'boolean'],
            ['stats'],
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
                'filters',
                'postFilters',
                'sorts',
                'aggregations',
                'suggesters',
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
     * Test getScrollDuration method.
     */
    public function testGetScrollDuration()
    {
        $search = new Search();
        $search->setScroll('10');
        $this->assertEquals(10, $search->getScrollDuration());
    }

    /**
     * Test addSort and getSorts methods.
     */
    public function testAddGetSorts()
    {
        $geoSortMock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Sort\GeoSort')
            ->disableOriginalConstructor()
            ->getMock();

        $search = new Search();
        $this->assertNull($search->getSorts());
        $search->addSort($geoSortMock);
        $this->assertArrayHasKey('_geo_distance', $search->getSorts()->toArray());
    }
}
