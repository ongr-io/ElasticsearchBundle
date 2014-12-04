<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result\Suggestion;

use ONGR\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\TermOption;

/**
 * Unit tests for completion option.
 */
class TermOptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if getters works as expected.
     */
    public function testGetters()
    {
        $option = new TermOption('test', 1.0, 2.0);
        $this->assertEquals(2.0, $option->getFreq());
    }
}
