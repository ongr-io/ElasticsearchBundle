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
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;

class JsonWriterTest extends TestCase
{
    use SetUpTearDownTrait;

    /**
     * {@inheritdoc}
     */
    public static function doSetUpBeforeClass()
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
        $metadata = ['count' => 2];
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
            $metadata,
            $documents,
            $expectedOutput,
        ];

        // Case #1 In case no "count" is provided.
        $metadata = [];
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
[],
{"_id":"doc1","title":"Document 1"},
{"_id":"doc2","title":"Document 2"}
]
OUT;

        $cases[] = [
            $metadata,
            $documents,
            $expectedOutput,
        ];

        // Case #2 In case no "count" or documents provided.
        $cases[] = [
            [],
            [],
            "[\n[]\n]",
        ];

        return $cases;
    }

    /**
     * Test for push().
     *
     * @param array  $metadata
     * @param array  $documents
     * @param string $expectedOutput
     *
     * @dataProvider getTestPushData()
     */
    public function testPush($metadata, $documents, $expectedOutput)
    {
        $filename = vfsStream::url('tmp/test.json');

        $writer = new JsonWriter($filename, $metadata);

        foreach ($documents as $document) {
            $writer->push($document);
        }

        $writer->finalize();

        $this->assertEquals($expectedOutput, file_get_contents($filename));
    }

    /**
     * Test for push() in case of too many documents passed.
     */
    public function testPushException()
    {
        $this->expectException(\OverflowException::class);

        $filename = vfsStream::url('tmp/test.json');

        $writer = new JsonWriter($filename, ['count' => 0]);
        $writer->push(null);
    }
}
