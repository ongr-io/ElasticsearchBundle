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

use ONGR\ElasticsearchBundle\Mapping\MappingTool;

/**
 * Test for comparing arrays/mappings.
 */
class MappingToolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testSymDiff.
     *
     * @return array
     */
    public function getTestSymDiffData()
    {
        return [
            // Case #0: no difference.
            [
                ['a' => 4, 'b' => 6],
                ['a' => 4, 'b' => 6],
                [],
            ],
            // Case #1: basic difference.
            [
                ['a' => 4, 'b' => 6, 'c' => 10],
                ['a' => 4, 'b' => 6],
                ['c' => 10],
            ],
            // Case #2: second array is different.
            [
                ['a' => 4, 'b' => 6],
                ['a' => 4, 'b' => 6, 'c' => 10],
                ['c' => 10],
            ],
            // Case #3: both arrays are missing data.
            [
                ['a' => 4, 'b' => 6],
                ['a' => 4, 'c' => 10],
                ['b' => 6, 'c' => 10],
            ],
            // Case #4: multi level difference.
            [
                [
                    'a' => 4,
                    'b' => 6,
                    'd' => [
                        'f' => 9,
                        'g' => 18,
                        'h' => ['p' => 78, 'foo'],
                    ],
                ],
                [
                    'a' => 4,
                    'd' => [
                        'f' => 9,
                        'g' => 19,
                        'r' => 7,
                        'h' => ['y' => 78],
                    ],
                ],
                [
                    'b' => 6,
                    'd' => [
                        'g' => 19,
                        'r' => 7,
                        'h' => ['p' => 78, 'y' => 78, 'foo'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests if arrays are compared as expected.
     *
     * @param array $array1   Array compare to.
     * @param array $array2   Array against.
     * @param array $expected Expected result.
     *
     * @dataProvider getTestSymDiffData
     */
    public function testSymDiff($array1, $array2, $expected)
    {
        $tool = new MappingTool($array1);
        $diff = $tool->symDifference($array2);

        $this->assertEquals($expected, $diff);
    }
}
