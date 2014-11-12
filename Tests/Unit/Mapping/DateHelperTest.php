<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use ONGR\ElasticsearchBundle\Mapping\DateHelper;

/**
 * Unit tests for date helper.
 */
class DateHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testParseString().
     *
     * @return array
     */
    public function getParseStringData()
    {
        $out = [];

        // Case #0: one day.
        $out[] = ['1d', 86400000];

        // Case #1: all unit types.
        $out[] = ['2w3d4h5m6ms', 1483500006];

        // Case #2: Invalid unit type.
        $out[] = ['2w3d4h5g6ms', 0, 'InvalidArgumentException', "Unknown time unit 'g'"];

        // Case #3: Invalid string.
        $out[] = ['ggg', 0, 'InvalidArgumentException', "Invalid time string 'ggg'"];

        return $out;
    }

    /**
     * Check if string are parsed correctly.
     *
     * @param string    $stringValue
     * @param int       $expectedValue
     * @param string    $expectedException
     * @param string    $exceptionMessage
     *
     * @dataProvider getParseStringData()
     */
    public function testParseString($stringValue, $expectedValue, $expectedException = null, $exceptionMessage = '')
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException, $exceptionMessage);
        }
        $this->assertEquals($expectedValue, DateHelper::parseString($stringValue));
    }
}
