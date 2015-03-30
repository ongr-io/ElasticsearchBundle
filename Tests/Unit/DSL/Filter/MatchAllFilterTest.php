<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\DSL\Filter;

use Ongr\ElasticsearchBundle\DSL\Filter\MatchAllFilter;

class MatchAllFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests GetType method, it should return 'match_all'.
     */
    public function testGetType()
    {
        $filter = new MatchAllFilter();
        $result = $filter->getType();
        $this->assertEquals('match_all', $result);
    }

    /**
     * Test toArray method.
     */
    public function testToArrayItShouldReturnStdClass()
    {
        $filter = new MatchAllFilter();
        $result = $filter->toArray();
        $expectedResult = new \stdClass();
        $this->assertEquals($expectedResult, $result);
    }
}
