<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Command;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Command\IndexExportCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IndexExportCommandTest extends AbstractElasticsearchTestCase
{
    protected function getDataArray()
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 'doc1',
                    'title' => 'Foo Product',
                    'number' => 5.00,
                ],
                [
                    '_id' => 'doc2',
                    'title' => 'Bar Product',
                    'number' => 8.33,
                ],
                [
                    '_id' => 'doc3',
                    'title' => 'Lao Product',
                    'number' => 1.95,
                ],
            ],
        ];
    }

    /**
     * Data provider for testIndexExport().
     *
     * @return array
     */
    public function getIndexExportData()
    {
        $out = [];

        // Case 0
        $options = ['--index' => DummyDocument::INDEX_NAME];
        $expectedResults = $this->transformDataToResult(DummyDocument::class);
        $out[] = [$options, $expectedResults];

        // Case 1: product type specified with chunk.
        $options = ['--chunk' => 1, '--index' => DummyDocument::INDEX_NAME];
        $expectedResults = $this->transformDataToResult(DummyDocument::class);
        $out[] = [$options, $expectedResults];

        // Case 2: without parameters.
        $options = [];
        $expectedResults = $this->transformDataToResult(DummyDocument::class);
        $out[] = [$options, $expectedResults];

        return $out;
    }

    /**
     * @dataProvider getIndexExportData()
     */
    public function testIndexExport(array $options, array $expectedResults)
    {
        $this->getIndex(DummyDocument::class);
        vfsStream::setup('tmp');
        $this->getCommandTester()->execute(
            array_merge(
                [
                    'command' => IndexExportCommand::NAME,
                    'filename' => vfsStream::url('tmp/test.json'),
                ],
                $options
            )
        );

        $results = $this->parseResult(vfsStream::url('tmp/test.json'), count($expectedResults));
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * Transforms data provider data to ElasticSearch expected result data structure.
     */
    private function transformDataToResult(string $class): array
    {
        $expectedResults = [];
        $index = $this->getIndex($class);

        foreach ($this->getDataArray()[$class] as $document) {
            $id = $document['_id'];
            unset($document['_id']);
            $expectedResults[] = [
                '_type' => $index->getTypeName(),
                '_id' => $id,
                '_source' => $document,
            ];
        }

        return $expectedResults;
    }

    /**
     * Returns export index command with assigned container.
     *
     * @return CommandTester
     */
    private function getCommandTester()
    {
        $indexExportCommand = new IndexExportCommand();
        $indexExportCommand->setContainer($this->getContainer());

        $app = new Application();
        $app->add($indexExportCommand);

        $command = $app->find(IndexExportCommand::NAME);
        $commandTester = new CommandTester($command);

        return $commandTester;
    }

    /**
     * Parses provided file and sorts results.
     *
     * @param string $filePath
     * @param int    $expectedCount
     *
     * @return array
     */
    private function parseResult($filePath, $expectedCount)
    {
        $this->assertFileExists($filePath);
        $results = json_decode(file_get_contents($filePath), true);

        $metadata = array_shift($results);

        $this->assertEquals($expectedCount, $metadata['count']);

        usort(
            $results,
            function ($a, $b) {
                if ($a['_type'] == $b['_type']) {
                    if ($a['_id'] == $b['_id']) {
                        return 0;
                    }

                    return $a['_id'] < $b['_id'] ? -1 : 1;
                }

                return $a['_type'] < $b['_type'] ? -1 : 1;
            }
        );

        return $results;
    }
}
