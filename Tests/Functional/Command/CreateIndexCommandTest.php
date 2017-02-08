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
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateIndexCommandTest extends AbstractElasticsearchTestCase
{
    const COMMAND_NAME = 'ongr:es:index:create';

    /**
     * Tests creating index in case of existing this index. Configuration from tests yaml.
     */
    public function testExecuteWhenIndexExists()
    {
        $manager = $this->getManager();

        $commandTester = $this->getCommandTester();
        $options = [];
        $arguments['command'] = self::COMMAND_NAME;
        $arguments['--if-not-exists'] = null;

        // Test if the command returns 0 or not
        $this->assertSame(
            0,
            $commandTester->execute($arguments, $options)
        );

        $expectedOutput = sprintf(
            '/Index `%s` already exists in `%s` manager./',
            $manager->getIndexName(),
            $manager->getName()
        );

        $this->assertRegExp($expectedOutput, $commandTester->getDisplay());
    }

    /**
     * Tests creating index. Configuration from tests yaml.
     */
    public function testIndexCreateWhenThereIsNoIndex()
    {
        $manager = $this->getManager();
        $manager->dropIndex();

        $this->assertFalse($manager->indexExists(), 'Index should not exist.');

        $this->assertSame(
            0,
            $this->getCommandTester()->execute(
                [
                    'command' => self::COMMAND_NAME
                ]
            )
        );

        $this->assertTrue($manager->indexExists(), 'Index should exist.');
    }

    /**
     * Testing if creating index with alias option will switch alias correctly to the new index.
     */
    public function testAliasIsCreatedCorrectly()
    {
        $commandTester = $this->getCommandTester();
        $manager = $this->getManager();
        $manager->dropIndex();

        $aliasName = $manager->getIndexName();

        $this->assertFalse($manager->indexExists());
        $this->assertFalse($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));

        $commandTester->execute(
            [
                'command' => self::COMMAND_NAME,
                '-t' => null,
                '-a' => null,
            ]
        );

        $indexName = $manager->getIndexName();
        $this->assertTrue($manager->getClient()->indices()->exists(['index' => $indexName]));
        $this->assertTrue($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));
        $aliases = $manager->getClient()->indices()->getAlias(['name' => $aliasName]);
        $indexNamesFromAlias = array_keys($aliases);
        $this->assertCount(1, $indexNamesFromAlias);
        $this->assertEquals($indexName, $indexNamesFromAlias[0]);
        $this->assertNotEquals($manager->getIndexName(), $aliasName);

        //Drop index manually.
        $manager->dropIndex();
    }

    /**
     * Testing if aliases are correctly changed from one index to the next after multiple command calls.
     */
    public function testAliasIsChangedCorrectly()
    {
        $commandTester = $this->getCommandTester();
        $manager = $this->getManager();
        $manager->dropIndex();

        $aliasName = $manager->getIndexName();

        $this->assertFalse($manager->indexExists());
        $this->assertFalse($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));

        $commandTester->execute(
            [
                'command' => self::COMMAND_NAME,
                '-t' => null,
                '-a' => null,
            ]
        );

        $indexName = $manager->getIndexName();
        $this->assertTrue($manager->getClient()->indices()->exists(['index' => $indexName]));
        $this->assertTrue($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));
        $this->assertNotEquals($manager->getIndexName(), $aliasName);

        $manager->setIndexName($aliasName);
        $commandTester->execute(
            [
                'command' => self::COMMAND_NAME,
                '-t' => null,
                '-a' => null,
            ]
        );

        $indexName2 = $manager->getIndexName();
        $this->assertTrue($manager->getClient()->indices()->exists(['index' => $indexName2]));
        $this->assertTrue($manager->getClient()->indices()->existsAlias(['name' => $aliasName]));

        //Drop index manually.
        $manager->setIndexName($indexName);
        $manager->dropIndex();
        $manager->setIndexName($indexName2);
        $manager->dropIndex();
    }

    /**
     * Tests if the json containing index mapping is returned when --dump option is provided
     */
    public function testIndexMappingDump()
    {
        $commandTester = $this->getCommandTester();
        $manager = $this->getManager();
        $manager->dropIndex();

        $this->assertFalse($manager->indexExists());
        $commandTester->execute(
            [
                'command' => self::COMMAND_NAME,
                '--dump' => null,
            ]
        );

        $this->assertContains(
            json_encode(
                $manager->getIndexMappings(),
                JSON_PRETTY_PRINT
            ),
            $commandTester->getDisplay()
        );
        $this->assertFalse($manager->indexExists());
    }

    /**
     * Returns command tester.
     *
     * @return CommandTester
     */
    private function getCommandTester()
    {
        $indexCreateCommand = new IndexCreateCommand();
        $indexCreateCommand->setContainer($this->getContainer());

        $app = new Application();
        $app->add($indexCreateCommand);

        $command = $app->find(self::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        return $commandTester;
    }
}
