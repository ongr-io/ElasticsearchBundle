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
     * Data provider for testPrettify.
     * 
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
        $pretty0 = $this->getFileContents('formatted_0.json');
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
        $pretty1 = $this->getFileContents('formatted_1.json');
        $out[] = [
            json_encode($data1),
            $pretty1,
        ];

        return $out;
    }

    /**
     * Tests prettify method.
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

    /**
     * Returns file contents from fixture.
     * 
     * @param string $filename
     *
     * @return string
     */
    private function getFileContents($filename)
    {
        $contents = file_get_contents(__DIR__ . '/../../app/fixture/JsonFormatter/' . $filename);

        // Checks for new line at the end of file.
        if (substr($contents, -1) == "\n") {
            $contents = substr($contents, 0, -1);
        }

        return $contents;
    }
}
