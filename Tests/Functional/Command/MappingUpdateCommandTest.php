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

use ONGR\ElasticsearchBundle\Command\MappingUpdateCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for type update command.
 */
class MappingUpdateCommandTest extends AbstractElasticsearchTestCase
{
    /**
     * Check if update works as expected.
     */
    public function testExecute()
    {
        $manager = $this->getManager();
        $manager->dropAndCreateIndex(true);

        $mapping = $manager->getClient()->indices()->getMapping(['index' => $manager->getIndexName()]);

        $this->assertCount(0, $mapping[$manager->getIndexName()]['mappings']);

        $command = new MappingUpdateCommand();
        $command->setContainer($this->getContainer());

        $app = new Application();
        $app->add($command);

        $commandToTest = $app->find('ongr:es:mapping:update');
        $commandTester = new CommandTester($commandToTest);

        $commandTester->execute(
            [
                'command' => $commandToTest->getName(),
                '--force' => true,
            ]
        );

        $expectedMapping = $manager->getMetadataCollector()->getClientMapping($manager->getConfig()['mappings']);
        $mapping = $manager->getClient()->indices()->getMapping(['index' => $manager->getIndexName()]);

        $this->assertEquals(
            asort($expectedMapping),
            asort($mapping[$manager->getIndexName()]['mappings'])
        );
    }
}
