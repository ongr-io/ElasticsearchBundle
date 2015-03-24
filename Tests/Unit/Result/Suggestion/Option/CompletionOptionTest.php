<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Result\Suggestion;

use Ongr\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption;

/**
 * Unit tests for completion option.
 */
class CompletionOptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if getters works as expected.
     */
    public function testGetters()
    {
        $completion = new CompletionOption('test', 1.0, ['my' => 'test']);
        $this->assertEquals('test', $completion->getText());
        $this->assertEquals(1.0, $completion->getScore());
        $this->assertEquals(['my' => 'test'], $completion->getPayload());
    }
}
