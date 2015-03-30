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

use Ongr\ElasticsearchBundle\Result\Suggestion\Option\PhraseOption;

/**
 * Unit tests for completion option.
 */
class PhraseOptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if getters works as expected.
     */
    public function testGetters()
    {
        $option = new PhraseOption('test', 1.0, 'highlighted');
        $this->assertEquals('highlighted', $option->getHighlighted());
    }
}
