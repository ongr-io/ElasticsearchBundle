<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Suggestion\Option;

use ONGR\ElasticsearchBundle\Result\Suggestion\Option\SimpleOption;

/**
 * Class SimpleOptionTest.
 */
class SimpleOptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests score setter.
     */
    public function testScoreSetter()
    {
        $simpleOption = new SimpleOption('text', 1);
        $this->assertEquals(1, $simpleOption->getScore());
        $simpleOption->setScore(2);
        $this->assertEquals(2, $simpleOption->getScore());
    }
}
