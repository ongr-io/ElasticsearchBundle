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

use ONGR\ElasticsearchBundle\DSL\Query\IdsQuery;
use ONGR\ElasticsearchBundle\DSL\Query\SpanMultiTermQuery;

class SpanMultiTermQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test is exception is thrown when query is not valid.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSpanMultiTermQueryIfExceptionIsThrown()
    {
        $spanMultiTerm = new SpanMultiTermQuery(new IdsQuery(['foo']));
        $spanMultiTerm->toArray();
    }
}
