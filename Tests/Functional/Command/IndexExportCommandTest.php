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

use ONGR\ElasticsearchBundle\Command\IndexExportCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IndexExportCommandTest extends AbstractElasticsearchTestCase
{
    const COMMAND_NAME = 'ongr:es:index:export';

    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'users' => [
                    [
                        '_id' => "4",
                        'name' => 'foo',
                    ],
                    [
                        '_id' => "5",
                        'name' => 'acme',
                    ],
                ],
                'product' => [
                    [
                        '_id' => "1",
                        'title' => 'foo',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => "2",
                        'title' => 'bar',
                        'price' => 32,
                    ],
                    [
                        '_id' => "3",
                        'title' => 'acme',
                        'price' => 20,
                    ],
                ],
            ],
        ];
    }

    /**
     * Transforms data provider data to elasticsearch expected result data structure.
     *
     * @param $type
     * @return array
     */
    private function transformDataToResult($type)
    {
        $expectedResults = [];

        foreach ($this->getDataArray()['default'][$type] as $document) {
            $id = $document['_id'];
            unset($document['_id']);
            $expectedResults[] = [
                '_type' => $type,
                '_id' => $id,
                '_source' => $document,
            ];
        }

        return $expectedResults;
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
        $options = ['--manager' => 'default'];
        $expectedResults = array_merge(
            $this->transformDataToResult('product'),
            $this->transformDataToResult('users')
        );
        $out[] = [$options, $expectedResults];

        // Case 1: product type specified.
        $options = ['--types' => ['product']];

        $expectedResults = $this->transformDataToResult('product');
        $out[] = [$options, $expectedResults];

        // Case 2: users type specified.
        $options = ['--types' => ['users']];

        $expectedResults = $this->transformDataToResult('users');
        $out[] = [$options, $expectedResults];

        // Case 3: product type specified with chunk.
        $options = ['--chunk' => 1, '--types' => ['product']];

        $expectedResults = $this->transformDataToResult('product');
        $out[] = [$options, $expectedResults];

        // Case 4: without parameters.
        $options = [];

        $expectedResults = array_merge(
            $this->transformDataToResult('product'),
            $this->transformDataToResult('users')
        );
        $out[] = [$options, $expectedResults];

        return $out;
    }

    /**
     * Test for index export command.
     *
     * @param array $options
     * @param array $expectedResults
     *
     * @dataProvider getIndexExportData()
     */
    public function testIndexExport($options, $expectedResults)
    {
        vfsStream::setup('tmp');

        $this->getCommandTester()->execute(
            array_merge(
                [
                    'command' => self::COMMAND_NAME,
                    'filename' => vfsStream::url('tmp/test.json'),
                ],
                $options
            )
        );

        $results = $this->parseResult(vfsStream::url('tmp/test.json'), count($expectedResults));
        $this->assertEquals($expectedResults, $results);
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

        $command = $app->find(self::COMMAND_NAME);
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
    protected function parseResult($filePath, $expectedCount)
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
