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

use ONGR\ElasticsearchBundle\Command\IndexDropCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DropIndexCommandTest extends AbstractElasticsearchTestCase
{
    /**
     * Tests dropping index. Configuration from tests yaml.
     */
    public function testExecute()
    {
        $manager = $this->getManager();

        $command = new IndexDropCommand();
        $command->setContainer($this->getContainer());

        $app = new Application();
        $app->add($command);

        // Does not drop index.
        $command = $app->find('ongr:es:index:drop');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );
        $this->assertTrue(
            $manager
                ->indexExists(),
            'Index should still exist.'
        );

        // Does drop index.
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--force' => true,
            ]
        );

        $this->assertFalse(
            $manager
                ->indexExists(),
            'Index should be dropped.'
        );
    }
}
