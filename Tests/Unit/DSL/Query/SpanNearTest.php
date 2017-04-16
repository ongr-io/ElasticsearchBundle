<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Query\SpanNearQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanTermQuery;

class SpanNearTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Missing slop parameter.
     *
     * @expectedException \LogicException
     */
    public function testSpanNearQueryExceptionWithoutSlopParameter()
    {
        $spanNearQuery = new SpanNearQuery([new SpanTermQuery('foo', 'bar')]);
    }
}
