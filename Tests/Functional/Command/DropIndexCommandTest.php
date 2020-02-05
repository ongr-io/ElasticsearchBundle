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
use ONGR\ElasticsearchBundle\Command\IndexDropCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DropIndexCommandTest extends AbstractElasticsearchTestCase
{
    public function testExecute()
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->dropAndCreateIndex();

        $command = new IndexDropCommand($this->getContainer());

        $app = new Application();
        $app->add($command);

        // Does not drop index.
        $command = $app->find(IndexDropCommand::NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $this->assertTrue(
            $index
                ->indexExists(),
            'Index should still exist.'
        );

        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--force' => true,
            ]
        );

        $this->assertFalse(
            $index
                ->indexExists(),
            'Index should be dropped.'
        );
    }
}
