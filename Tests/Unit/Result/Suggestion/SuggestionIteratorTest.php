<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Suggestion;

use ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionIterator;

/**
 * Class SuggestionIteratorTest.
 */
class SuggestionIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests exception on set action.
     */
    public function testSetException()
    {
        $optionIterator = new SuggestionIterator([]);
        $this->setExpectedException('LogicException', 'Data of this iterator can not be changed after initialization.');
        $optionIterator[0] = 1;
    }

    /**
     * Tests exception on unset action.
     */
    public function testUnsetException()
    {
        $optionIterator = new SuggestionIterator([]);
        $this->setExpectedException('LogicException', 'Data of this iterator can not be changed after initialization.');
        unset($optionIterator[0]);
    }
}
