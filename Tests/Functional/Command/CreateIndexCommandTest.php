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
use ONGR\ElasticsearchBundle\Command\IndexCreateCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateIndexCommandTest extends AbstractElasticsearchTestCase
{
    public function testExecuteWhenIndexExists()
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->dropAndCreateIndex();

        $commandTester = $this->getCommandTester();
        $options = [];
        $arguments['command'] = IndexCreateCommand::NAME;
        $arguments['--if-not-exists'] = null;

        // Test if the command returns 0 or not
        $this->assertSame(
            0,
            $commandTester->execute($arguments, $options)
        );

        $expectedOutput = sprintf(
            '/Index `%s` already exists./',
            $index->getIndexName()
        );

        $this->assertRegExp($expectedOutput, $commandTester->getDisplay());
    }

    public function testIndexCreateWhenThereIsNoIndex()
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->dropIndex();

        $this->assertFalse($index->indexExists(), 'Index should not exist.');

        $this->assertSame(
            0,
            $this->getCommandTester()->execute(
                [
                    'command' => IndexCreateCommand::NAME
                ]
            )
        );

        $this->assertTrue($index->indexExists(), 'Index should exist.');
    }

    public function testAliasIsCreatedCorrectly()
    {
        $commandTester = $this->getCommandTester();

        $index = $this->getIndex(DummyDocument::class);
        $index->dropIndex();

        $aliasName = $index->getIndexName();

        $this->assertFalse($index->indexExists());
        $this->assertFalse($index->getClient()->indices()->existsAlias(['name' => $aliasName]));

        $commandTester->execute(
            [
                'command' => IndexCreateCommand::NAME,
                '-t' => null,
                '-a' => null,
            ]
        );

        $this->assertTrue($index->getClient()->indices()->existsAlias(['name' => $aliasName]));
    }

    /**
     * Testing if aliases are correctly changed from one index to the next after multiple command calls.
     */
    public function testAliasIsChangedCorrectly()
    {
        $commandTester = $this->getCommandTester();

        $index = $this->getIndex(DummyDocument::class);
        $index->dropIndex();

        $aliasName = $index->getIndexName();

        $this->assertFalse($index->indexExists());
        $this->assertFalse($index->getClient()->indices()->existsAlias(['name' => $aliasName]));

        $commandTester->execute(
            [
                'command' => IndexCreateCommand::NAME,
                '-t' => null,
                '-a' => null,
            ]
        );

        $aliasIndex = array_keys($index->getClient()->indices()->getAlias(['name' => $aliasName]));
        $createdIndexName = array_shift($aliasIndex);

        $this->assertTrue($index->getClient()->indices()->exists(['index' => $createdIndexName]));
        $this->assertTrue($index->getClient()->indices()->existsAlias(['name' => $aliasName]));

        $this->assertNotEquals($aliasName, $createdIndexName);

        $commandTester->execute(
            [
                'command' => IndexCreateCommand::NAME,
                '-t' => null,
                '-a' => null,
            ]
        );

        $aliasIndex = array_keys($index->getClient()->indices()->getAlias(['name' => $aliasName]));
        $createdIndexName2 = array_shift($aliasIndex);

        $this->assertTrue($index->getClient()->indices()->exists(['index' => $createdIndexName]));
        $this->assertTrue($index->getClient()->indices()->exists(['index' => $createdIndexName2]));

        $this->assertTrue($index->getClient()->indices()->existsAlias(['name' => $aliasName]));

        //Drop index manually.
        $index->getClient()->indices()->delete(['index' => $createdIndexName.",".$createdIndexName2]);
    }

    /**
     * Tests if the json containing index mapping is returned when --dump option is provided
     */
    public function testIndexMappingDump()
    {
        $commandTester = $this->getCommandTester();
        $index = $this->getIndex(DummyDocument::class);
        $index->dropIndex();

        $this->assertFalse($index->indexExists());
        $commandTester->execute(
            [
                'command' => IndexCreateCommand::NAME,
                '--dump' => null,
            ]
        );

        $this->assertContains(
            json_encode(
                $index->getIndexSettings()->getIndexMetadata(),
                JSON_PRETTY_PRINT
            ),
            $commandTester->getDisplay()
        );
        $this->assertFalse($index->indexExists());
    }

    /**
     * Returns command tester.
     *
     * @return CommandTester
     */
    private function getCommandTester()
    {
        $indexCreateCommand = new IndexCreateCommand($this->getContainer());

        $app = new Application();
        $app->add($indexCreateCommand);

        $command = $app->find(IndexCreateCommand::NAME);
        $commandTester = new CommandTester($command);

        return $commandTester;
    }
}
