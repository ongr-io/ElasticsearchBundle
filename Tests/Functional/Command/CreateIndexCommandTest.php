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
     *
     * @param string $managerName
     * @param array  $arguments
     * @param array  $options
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecuteWithIndexExistence($managerName, $arguments, $options)
    {
        $manager = $this->getManager($managerName);

        if (!$manager->indexExists()) {
            $manager->createIndex();
        }

        try {
            $arguments['--if-not-exists'] = null;
            $this->runIndexCreateCommand($managerName, $arguments, $options);
        } catch (\Exception $ex) {
            $message = $ex->getMessage();
            $expectedClassName = 'Elasticsearch\\Common\\Exceptions\\BadRequest400Exception';
            $isExpectedException = $ex instanceof $expectedClassName;
            if ($isExpectedException) {
                $this->assertNotContains('IndexAlreadyExistsException', $message);
            }
        }
        $manager->dropIndex();
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
        $app = new Application();
        $app->add($this->getCreateCommand());

        // Creates index.
        $command = $app->find('ongr:es:index:create');
        $commandTester = new CommandTester($command);
        $arguments['command'] = $command->getName();
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
}
