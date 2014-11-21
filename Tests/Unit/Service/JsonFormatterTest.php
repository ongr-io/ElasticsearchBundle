<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service;

use ONGR\ElasticsearchBundle\Service\JsonFormatter;

class JsonFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getTestPrettifyData()
    {
        $out = [];

        // Case #0: simple.
        $data0 = [
            'total' => 0,
            'raw' => []
        ];
        $pretty0 = <<<OUT
{
    "total": 0,
    "raw": [
        
    ]
}
OUT;
        $out[] = [
            json_encode($data0),
            $pretty0,
        ];

        // Case #1: more data.
        $data1 = [
            'total' => [
                'hit' => 1,
                'name' => 'bunch',
            ],
            'hits' => [
                ['_id' => 1],
                ['_id' => 2],
            ],
            [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => []
                        ]
                    ]
                ]
            ]
        ];
        $pretty1 = <<<OUT
{
    "total": {
        "hit": 1,
        "name": "bunch"
    },
    "hits": [
        {
            "_id": 1
        },
        {
            "_id": 2
        }
    ],
    "0": {
        "level1": {
            "level2": {
                "level3": {
                    "level4": [
                        
                    ]
                }
            }
        }
    }
}
OUT;
        $out[] = [
            json_encode($data1),
            $pretty1,
        ];

        return $out;
    }

    /**
     * Tests prettfy method.
     *
     * @param string $inlineJson
     * @param string $prettyJson
     *
     * @dataProvider getTestPrettifyData
     */
    public function testPrettify($inlineJson, $prettyJson)
    {
        $this->assertEquals($prettyJson, JsonFormatter::prettify($inlineJson));
    }
}
