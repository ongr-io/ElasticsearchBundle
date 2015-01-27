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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DropIndexCommandTest extends AbstractCommandTestCase
{
    /**
     * Execution data provider.
     *
     * @return array
     */
    public function getTestExecuteData()
    {
        return [
            ['default'],
            ['bar'],
        ];
    }

    /**
     * Tests dropping index. Configuration from tests yaml.
     *
     * @param string $argument
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecute($argument)
    {
        $manager = $this->getManager($argument);
        $manager->getConnection()->createIndex();

        $app = new Application();
        $app->add($this->getDropCommand());

        // Does not drop index.
        $command = $app->find('es:index:drop');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--manager' => $argument,
            ]
        );
        $this->assertTrue(
            $manager
                ->getConnection()
                ->indexExists(),
            'Index should still exist.'
        );

        // Does drop index.
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--manager' => $argument,
                '--force' => true,
            ]
        );

        $this->assertFalse(
            $manager
                ->getConnection()
                ->indexExists(),
            'Index should be dropped.'
        );
    }

    /**
     * Returns drop index command with assigned container.
     *
     * @return IndexDropCommand
     */
    protected function getDropCommand()
    {
        $command = new IndexDropCommand();
        $command->setContainer($this->getContainer());

        return $command;
    }
}
