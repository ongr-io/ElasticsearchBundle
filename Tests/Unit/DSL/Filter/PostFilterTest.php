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

use Ongr\ElasticsearchBundle\DSL\Filter\PostFilter;

class PostFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests GetType method.
     */
    public function testIfGetType()
    {
        $filter = new PostFilter();
        $this->assertEquals('post_filter', $filter->getType());
    }

    /**
     * Test if function is returning False.
     */
    public function testIfIsRelevantFunctionIsReturningFalse()
    {
        $bool = new PostFilter();
        $this->assertFalse($bool->isRelevant());
    }
}
