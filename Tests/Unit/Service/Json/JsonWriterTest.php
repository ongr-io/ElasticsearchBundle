<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service\Json;

use ONGR\ElasticsearchBundle\Service\Json\JsonWriter;
use org\bovigo\vfs\vfsStream;

class JsonWriterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        vfsStream::setup('tmp');
    }

    /**
     * Data provider for testPush().
     *
     * @return array
     */
    public function getTestPushData()
    {
        $cases = [];

        // Case #0 Standard case.
        $documents = [
            [
                '_id' => 'doc1',
                'title' => 'Document 1',
            ],
            [
                '_id' => 'doc2',
                'title' => 'Document 2',
            ],
        ];
        $expectedOutput = <<<OUT
[
{"count":2},
{"_id":"doc1","title":"Document 1"},
{"_id":"doc2","title":"Document 2"}
]
OUT;

        $cases[] = [
            $documents,
            $expectedOutput,
        ];

        // Case #1 In case no "count" is provided.
        $documents = [
            [
                '_id' => 'doc1',
                'title' => 'Document 1',
            ],
            [
                '_id' => 'doc2',
                'title' => 'Document 2',
            ],
            [
                '_id' => 'doc3',
                'title' => 'Document 3',
            ],
        ];
        $expectedOutput = <<<OUT
[
{"count":3},
{"_id":"doc1","title":"Document 1"},
{"_id":"doc2","title":"Document 2"},
{"_id":"doc3","title":"Document 3"}
]
OUT;

        $cases[] = [
            $documents,
            $expectedOutput,
        ];

        $expectedOutput = <<<OUT
[
{"count":0}
]
OUT;

        // Case #2 In case no "count" or documents provided.
        $cases[] = [
            [],
            $expectedOutput,
        ];

        return $cases;
    }

    /**
     * Test for push().
     *
     * @param array  $documents
     * @param string $expectedOutput
     *
     * @dataProvider getTestPushData()
     */
    public function testPush($documents, $expectedOutput)
    {
        $filename = vfsStream::url('tmp/test.json');

        $writer = new JsonWriter($filename, count($documents));

        foreach ($documents as $document) {
            $writer->push($document);
        }

        $writer->finalize();

        $this->assertEquals($expectedOutput, file_get_contents($filename));
    }

    /**
     * Test for push() in case of too many documents passed.
     *
     * @expectedException \OverflowException
     */
    public function testPushException()
    {
        $filename = vfsStream::url('tmp/test.json');

        $writer = new JsonWriter($filename, 0);
        $writer->push(null);
    }
}
