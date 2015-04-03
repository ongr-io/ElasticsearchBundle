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

use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\DSL\Sort\AbstractSort;

/**
 * Unit test for Search.
 */
class SearchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns a mock object of AbstractSort class.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getAbstractSortMock()
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Sort\AbstractSort')
            ->setConstructorArgs(['field', AbstractSort::ORDER_ASC, AbstractSort::MODE_AVG])
            ->getMock();

        return $mock;
    }

    /**
     * Returns a mock object of AbstractSuggester class.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getAbstractSuggesterMock()
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\DSL\Suggester\AbstractSuggester')
            ->setConstructorArgs(['', ''])
            ->getMock();

        return $mock;
    }

    /**
     * Tests addSort method.
     */
    public function testAddSort()
    {
        $search = new Search();
        $this->assertEmpty($search->getSorts(), 'Sort array should be empty.');

        $search->addSort($this->getAbstractSortMock());
        $this->assertNotEmpty($search->getSorts(), 'Sort array should contain one added sort.');
    }

    /**
     * Tests addPostFilter method.
     */
    public function testAddPostFilter()
    {
        $search = new Search();
        $this->assertEmpty($search->getPostFilters(), 'Post filters array should be empty.');

        $search->addPostFilter($this->getMock('ONGR\ElasticsearchBundle\DSL\BuilderInterface'));
        $this->assertNotEmpty($search->getPostFilters(), 'Post filters array should contain one added filter.');
    }

    /**
     * Tests addAggregation method.
     */
    public function testAddAggregation()
    {
        $search = new Search();
        $this->assertEmpty($search->getAggregations(), 'Aggregations array should be empty.');

        $search->addAggregation($this->getMock('ONGR\ElasticsearchBundle\DSL\NamedBuilderInterface'));
        $this->assertNotEmpty($search->getAggregations(), 'Aggregations array should contain one added aggregation.');
    }

    /**
     * Tests addFilter method.
     */
    public function testAddFilter()
    {
        $search = new Search();
        $this->assertEmpty($search->getFilters(), 'Filters array should be empty.');

        $search->addFilter($this->getMock('ONGR\ElasticsearchBundle\DSL\BuilderInterface'));
        $this->assertNotEmpty($search->getFilters(), 'Filters array should contain one added filter.');
    }

    /**
     * Tests addSuggester method.
     */
    public function testAddSuggester()
    {
        $search = new Search();
        $this->assertEmpty($search->getSuggesters(), 'Suggesters array should be empty.');

        $search->addSuggester($this->getAbstractSuggesterMock());
        $this->assertNotEmpty($search->getSuggesters(), 'Suggesters array should contain one added suggester.');
    }

    /**
     * Data provider for testGetQueryParams().
     *
     * @return array
     */
    public function getTestGetQueryParamsData()
    {
        return [
            [[]],
            [
                [
                    'scroll' => '5m',
                    'search_type' => 'testSearchType',
                    'preference' => 'testPreference',
                ],
                '5m',
                'testSearchType',
                'testPreference',
            ],
            [
                [
                    'scroll' => '5m',
                    'search_type' => 'testSearchType',
                    'preference' => 'preference1;preference2',
                ],
                '5m',
                'testSearchType',
                ['preference1', 'preference2'],
            ],
        ];
    }

    /**
     * Tests getQueryParams method.
     *
     * @param array        $expected       Expected result.
     * @param string|null  $scrollDuration Scroll duration.
     * @param string       $searchType     Search type.
     * @param string|array $preference     Preference.
     *
     * @dataProvider getTestGetQueryParamsData
     */
    public function testGetQueryParams($expected, $scrollDuration = null, $searchType = null, $preference = null)
    {
        $search = new Search();
        $search->setScroll($scrollDuration);
        $search->setSearchType($searchType);
        $search->setPreference($preference);

        $result = $search->getQueryParams();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests toArray method when searching query is empty.
     */
    public function testToArrayEmptyQuery()
    {
        $search = new Search();
        $result = $search->toArray();
        $this->assertEquals([], $result);
    }
}
