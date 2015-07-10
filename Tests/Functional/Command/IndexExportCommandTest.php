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
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IndexExportCommandTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 1,
                        'title' => 'foo',
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 32,
                    ],
                ],
                'fooContent' => [
                    [
                        '_id' => 1,
                        'header' => 'test_1',
                    ],
                    [
                        '_id' => 2,
                        'header' => 'test_2',
                    ],
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

        // Case 1: chunk specified.
        $options = ['--chunk' => 1];
        $expectedResults = [
            [
                '_id' => '1',
                '_type' => 'fooContent',
                '_source' => [
                    'header' => 'test_1',
                ],
            ],
            [
                '_id' => '2',
                '_type' => 'fooContent',
                '_source' => [
                    'header' => 'test_2',
                ],
            ],
            [
                '_id' => '1',
                '_type' => 'product',
                '_source' => [
                    'title' => 'foo',
                    'price' => 10.45,
                ],
            ],
            [
                '_id' => '2',
                '_type' => 'product',
                '_source' => [
                    'title' => 'bar',
                    'price' => 32,
                ],
            ],
        ];

        $out[] = [$options, $expectedResults];

        // Case 1: types specified.
        $options = ['--types' => ['product']];
        $expectedResults = [
            [
                '_id' => '1',
                '_type' => 'product',
                '_source' => [
                    'title' => 'foo',
                    'price' => 10.45,
                ],
            ],
            [
                '_id' => '2',
                '_type' => 'product',
                '_source' => [
                    'title' => 'bar',
                    'price' => 32,
                ],
            ],
        ];

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
        $app = new Application();
        $app->add($this->getExportCommand());

        vfsStream::setup('tmp');

        $command = $app->find('ongr:es:index:export');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(
                [
                    'command' => $command->getName(),
                    'filename' => vfsStream::url('tmp/test.json'),
                ],
                $options
            )
        );

        $results = $this->parseResult(vfsStream::url('tmp/test.json'), count($expectedResults));
        $this->assertEquals($expectedResults, $results, null, 0.05);
    }

    /**
     * Returns export index command with assigned container.
     *
     * @return IndexExportCommand
     */
    protected function getExportCommand()
    {
        $command = new IndexExportCommand();
        $command->setContainer($this->getContainer());

        return $command;
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
        $this->fileExists($filePath);
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
