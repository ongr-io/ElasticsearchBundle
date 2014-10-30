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

use ONGR\ElasticsearchBundle\Service\Json\JsonReader;
use org\bovigo\vfs\vfsStream;

class JsonReaderTest extends \PHPUnit_Framework_TestCase
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
     * Data provider for testReader().
     *
     * @return array
     */
    public function getTestReaderData()
    {
        $cases = [];

        // Case #0 Default case.
        $contents = <<<OUT
[
{"count":2},
{"_type":"doc","_id":"doc1","_source":{"title":"Document 1"}},
{"_type":"doc","_id":"doc2","_source":{"title":"Document 2"}}
]
OUT;

        $expectedDocuments = [
            (object)['_id' => 'doc1', 'title' => 'Document 1'],
            (object)['_id' => 'doc2', 'title' => 'Document 2'],
        ];

        $cases[] = [$contents, $expectedDocuments];

        // Case #1 Empty metadata.
        $contents = <<<OUT
[
{},
{"_type":"doc","_id":"doc1","_source":{"title":"Document 1"}},
{"_type":"doc","_id":"doc2","_source":{"title":"Document 2"}}
]
OUT;

        $expectedDocuments = [
            (object)['_id' => 'doc1', 'title' => 'Document 1'],
            (object)['_id' => 'doc2', 'title' => 'Document 2'],
        ];

        $cases[] = [$contents, $expectedDocuments];

        // Case #2 No metadata or documents.
        $cases[] = ["[\n[]\n]", []];

        return $cases;
    }

    /**
     * Test for reader.
     *
     * @param string $contents
     * @param array  $expectedDocuments
     *
     * @dataProvider getTestReaderData()
     */
    public function testReader($contents, $expectedDocuments)
    {
        $managerMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $filename = vfsStream::url('tmp/test.json');
        file_put_contents($filename, $contents);

        $reader = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Service\Json\JsonReader')
            ->setConstructorArgs([$managerMock, $filename])
            ->setMethods(['getConverter'])
            ->getMock();

        $reader
            ->expects($this->any())
            ->method('getConverter')
            ->will($this->returnValue($this->getConverterMock()));

        $documents = [];
        foreach ($reader as $key => $document) {
            $documents[$key] = $document;
        }

        $this->assertEquals($expectedDocuments, $documents);
    }

    /**
     * Data provider for testReaderRaw().
     *
     * @return array
     */
    public function getTestReaderRawData()
    {
        $cases = [];

        // Case #0 Default case.
        $contents = <<<OUT
[
{"count":2},
{"_type":"doc","_id":"doc1","_source":{"title":"Document 1"}},
{"_type":"doc","_id":"doc2","_source":{"title":"Document 2"}}
]
OUT;

        $expectedDocuments = [
            ['_type' => 'doc', '_id' => 'doc1', '_score' => null, '_source' => ['title' => 'Document 1']],
            ['_type' => 'doc', '_id' => 'doc2', '_score' => null, '_source' => ['title' => 'Document 2']],
        ];

        $cases[] = [$contents, $expectedDocuments];

        // Case #1 No metadata or documents.
        $cases[] = ["[\n[]\n]", []];

        return $cases;
    }

    /**
     * Test for reader in case no converter.
     *
     * @param string $contents
     * @param array  $expectedDocuments
     *
     * @dataProvider getTestReaderRawData()
     */
    public function testReaderRaw($contents, $expectedDocuments)
    {
        $managerMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $filename = vfsStream::url('tmp/test.json');
        file_put_contents($filename, $contents);

        $reader = new JsonReader($managerMock, $filename, false);

        $documents = [];
        foreach ($reader as $key => $document) {
            $documents[$key] = $document;
        }

        $this->assertEquals($expectedDocuments, $documents);
    }

    /**
     * Tests cannot open file exception.
     *
     * @expectedException \LogicException
     */
    public function testCannotOpenFile()
    {
        $reader = new JsonReader(null, 'vfs://tmp/foo.json');
        $reader->current();
    }

    /**
     * Test with file that has bad structure.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testReadMetadataException()
    {
        $contents = <<<OUT
{"count":1},
{"_type":"doc","_id":"doc1","_source":{"title":"Document 1"}}
OUT;

        $filename = vfsStream::url('tmp/test.json');
        file_put_contents($filename, $contents);

        $reader = new JsonReader(null, $filename);
        $reader->getMetadata();
    }

    /**
     * Tests count method.
     */
    public function testCount()
    {
        $contents = <<<OUT
[
{"count":13},
]
OUT;
        $filename = vfsStream::url('tmp/test.json');
        file_put_contents($filename, $contents);

        $reader = new JsonReader(null, $filename);
        $this->assertEquals(13, $reader->count(), 'should read metadata from stream');
        $this->assertEquals(13, $reader->count(), 'should read metadata from cache');
    }

    /**
     * Tests count method exception.
     *
     * @expectedException \LogicException
     */
    public function testCountException()
    {
        $contents = <<<OUT
[
{},
]
OUT;
        $filename = vfsStream::url('tmp/test.json');
        file_put_contents($filename, $contents);

        $reader = new JsonReader(null, $filename);
        $reader->count();
    }

    /**
     * Tests getConverter method.
     */
    public function testGetConverter()
    {
        $managerMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
            ->disableOriginalConstructor()
            ->setMethods(['getTypesMapping', 'getBundlesMapping'])
            ->getMock();
        $managerMock
            ->expects($this->once())
            ->method('getTypesMapping')
            ->will($this->returnValue(['types']));
        $managerMock
            ->expects($this->once())
            ->method('getBundlesMapping')
            ->will($this->returnValue(['bundles']));

        $reader = new JsonReader($managerMock, 'tmp/test.json');

        $reflection = new \ReflectionObject($reader);
        $method = $reflection->getMethod('getConverter');
        $method->setAccessible(true);
        $converter = $method->invoke($reader, []);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\Converter', $converter);
    }

    /**
     * Test current.
     */
    public function testCurrent()
    {
        $contents = <<<OUT
[
{"count":2},
{"_type":"doc","_id":"doc1","_source":{"title":"Document 1"}}
]
OUT;
        $filename = vfsStream::url('tmp/test.json');
        file_put_contents($filename, $contents);

        $reader = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Service\Json\JsonReader')
            ->setConstructorArgs([null, $filename])
            ->setMethods(['getConverter'])
            ->getMock();

        $reader
            ->expects($this->any())
            ->method('getConverter')
            ->will($this->returnValue($this->getConverterMock()));

        $reader->getMetadata();

        $expected = new \stdClass();
        $expected->_id = 'doc1';
        $expected->title = 'Document 1';

        $this->assertEquals($expected, $reader->current(), 'should load from stream');
        $this->assertEquals($expected, $reader->current(), 'should load from cache');
        $reader->next();
        $this->assertEquals(null, $reader->current(), 'should be end of stream');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getConverterMock()
    {
        $converterMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->setMethods(['convertToDocument'])
            ->getMock();

        $converterMock
            ->expects($this->any())
            ->method('convertToDocument')
            ->will(
                $this->returnCallback(
                    function ($raw) {
                        return (object)array_merge(['_id' => $raw['_id']], $raw['_source']);
                    }
                )
            );

        return $converterMock;
    }
}
