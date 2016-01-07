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

use ONGR\ElasticsearchBundle\Command\IndexCreateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateIndexCommandTest extends AbstractCommandTestCase
{
    /**
     * Execution data provider.
     *
     * @return array
     */
    public function getTestExecuteData()
    {
        return [
            [
                'foo',
                [
                    '--no-mapping' => null,
                ],
                [],
            ],
            [
                'default',
                [],
                [],
            ],
        ];
    }

    /**
     * Tests creating index. Configuration from tests yaml.
     *
     * @param string $managerName
     * @param array  $arguments
     * @param array  $options
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecute($managerName, $arguments, $options)
    {
        $manager = $this->getManager($managerName);

        if ($manager->indexExists()) {
            $manager->dropIndex();
        }

        $this->runIndexCreateCommand($managerName, $arguments, $options);

        $this->assertTrue($manager->indexExists(), 'Index should exist.');
        $manager->dropIndex();
    }

    /**
     * Tests creating index in case of existing this index. Configuration from tests yaml.
     */
    public function testExecuteWhenIndexExists()
    {
        $manager = $this->getManager();

        // Initialize command
        $commandName = 'ongr:es:index:create';
        $commandTester = $this->getCommandTester($commandName);
        $options = [];
        $arguments['command'] = $commandName;
        $arguments['--manager'] = $manager->getName();
        $arguments['--if-not-exists'] = null;

        // Test if the command returns 0 or not
        $this->assertSame(
            0,
            $commandTester->execute($arguments, $options)
        );

        $expectedOutput = sprintf(
            'Index `%s` already exists in `%s` manager.',
            $manager->getIndexName(),
            $manager->getName()
        );

        // Test if the command output matches the expected output or not
        $this->assertStringMatchesFormat($expectedOutput . '%a', $commandTester->getDisplay());
    }

    /**
     * Tests if right exception is thrown when manager is read only.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Create index operation is not permitted.
     */
    public function testCreateIndexWhenManagerIsReadOnly()
    {
        $manager = $this->getContainer()->get('es.manager.readonly');
        $manager->createIndex();
    }

    /**
     * Testing if creating index with alias option will switch alias correctly to the new index.
     */
    public function testAliasIsCreatedCorrectly()
    {
        $manager = $this->getManager();

        $aliasName = $manager->getIndexName();
        $finder = $this->getContainer()->get('es.client.index_suffix_finder');
        $finder->setNextFreeIndex($manager);
        $oldIndexName = $manager->getIndexName();
        $manager->createIndex();

        $this->assertTrue($manager->indexExists());
        $this->assertFalse($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));

        $this->runIndexCreateCommand($manager->getName(), ['--time' => null, '--alias' => null], []);

        $aliases = $manager->getClient()->indices()->getAlias(['name' => $aliasName]);
        $newIndexNames = array_keys($aliases);

        $this->assertCount(1, $newIndexNames);
        $this->assertTrue($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));
        $this->assertNotEquals($manager->getIndexName(), $newIndexNames);

        $manager->setIndexName($newIndexNames[0]);
        $manager->dropIndex();
        $manager->setIndexName($oldIndexName);
        $manager->dropIndex();
    }

    /**
     * Runs the index create command.
     *
     * @param string $managerName
     * @param array  $arguments
     * @param array  $options
     */
    protected function runIndexCreateCommand($managerName, array $arguments = [], array $options = [])
    {
        // Creates index.
        $commandName = 'ongr:es:index:create';
        $commandTester = $this->getCommandTester($commandName);
        $arguments['command'] = $commandName;
        $arguments['--manager'] = $managerName;

        $commandTester->execute($arguments, $options);
    }

    /**
     * Returns create index command with assigned container.
     *
     * @return IndexCreateCommand
     */
    protected function getCreateCommand()
    {
        $command = new IndexCreateCommand();
        $command->setContainer($this->getContainer());

        return $command;
    }

    /**
     * Returns command tester.
     * @param string commandName
     *
     * @return CommandTester
     */
    protected function getCommandTester($commandName)
    {
        $app = new Application();
        $app->add($this->getCreateCommand());

        $command = $app->find($commandName);
        $commandTester = new CommandTester($command);

        return $commandTester;
    }
}
