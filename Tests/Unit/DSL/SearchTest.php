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
        $this->assertEmpty($search->getSorts());

        $search->addSort($this->getAbstractSortMock());
        $this->assertNotEmpty($search->getSorts());
    }

    /**
     * Tests addPostFilter method.
     */
    public function testAddPostFilter()
    {
        $search = new Search();
        $this->assertEmpty($search->getPostFilters());

        $search->addPostFilter($this->getMock('ONGR\ElasticsearchBundle\DSL\BuilderInterface'));
        $this->assertNotEmpty($search->getPostFilters());
    }

    /**
     * Tests addAggregation method.
     */
    public function testAddAggregation()
    {
        $search = new Search();
        $this->assertEmpty($search->getAggregations());

        $search->addAggregation($this->getMock('ONGR\ElasticsearchBundle\DSL\NamedBuilderInterface'));
        $this->assertNotEmpty($search->getAggregations());
    }

    /**
     * Tests addFilter method.
     */
    public function testAddFilter()
    {
        $search = new Search();
        $this->assertEmpty($search->getFilters());

        $search->addFilter($this->getMock('ONGR\ElasticsearchBundle\DSL\BuilderInterface'));
        $this->assertNotEmpty($search->getFilters());
    }

    /**
     * Tests addSuggester method.
     */
    public function testAddSuggester()
    {
        $search = new Search();
        $this->assertEmpty($search->getSuggesters());

        $search->addSuggester($this->getAbstractSuggesterMock());
        $this->assertNotEmpty($search->getSuggesters());
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
