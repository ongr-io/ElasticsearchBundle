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

use ONGR\ElasticsearchBundle\Command\IndexImportCommand;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IndexImportCommandTest extends ElasticsearchTestCase
{
    /**
     * Data provider for testIndexImport.
     *
     * @return array
     */
    public function bulkSizeProvider()
    {
        return [
            [10, 9, 'command_import_9.json'],
            [10, 10, 'command_import_10.json'],
            [10, 11, 'command_import_11.json'],
            [5, 20, 'command_import_20.json'],
        ];
    }

    /**
     * Test for index import command.
     *
     * @param int    $bulkSize
     * @param int    $realSize
     * @param string $filename
     *
     * @dataProvider bulkSizeProvider
     */
    public function testIndexImport($bulkSize, $realSize, $filename)
    {
        $app = new Application();
        $app->add($this->getImportCommand());

        $command = $app->find('ongr:es:index:import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--raw' => true,
                'filename' => __DIR__ . '/../../app/fixture/Json/' . $filename,
                '--bulk-size' => $bulkSize,
            ]
        );

        $manager = $this->getManager('default', false);
        $repo = $manager->getRepository('AcmeTestBundle:Product');
        $search = $repo
            ->createSearch()
            ->addQuery(new MatchAllQuery())
            ->setSize($realSize);
        $results = $repo->execute($search);

        $ids = [];
        foreach ($results as $doc) {
            $ids[] = substr($doc->_id, 3);
        }
        sort($ids);
        $data = range(1, $realSize);
        $this->assertEquals($data, $ids);
    }

    /**
     * Returns import index command with assigned container.
     *
     * @return IndexImportCommand
     */
    protected function getImportCommand()
    {
        $command = new IndexImportCommand();
        $command->setContainer($this->getContainer());

        return $command;
    }
}
