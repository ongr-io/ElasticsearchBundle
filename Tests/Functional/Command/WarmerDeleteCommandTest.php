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

use ONGR\ElasticsearchBundle\Command\WarmerDeleteCommand;
use ONGR\ElasticsearchBundle\Test\DelayedObjectWrapper;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests es warmer delete command.
 */
class WarmerDeleteCommandTest extends ElasticsearchTestCase
{
    /**
     * @return array
     */
    public function getTestExecuteData()
    {
        return [
            [
                "All warmers have been deleted from manager named `default`\n",
                [],
            ],
            [
                "`test_foo_warmer` warmer(s) have been deleted from manager named `default`\n",
                [
                    'names' => ['test_foo_warmer'],
                ],
            ],
        ];
    }

    /**
     * Tests if warmers are being deleted from index if command is executed.
     *
     * @param string $expected
     * @param array  $arguments
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecute($expected, $arguments = [])
    {
        $app = new Application();
        $app->add($this->getCommand());
        $command = $app->find('ongr:es:warmer:delete');
        $commandTester = new CommandTester($command);
        $connection = DelayedObjectWrapper::wrap($this->getManager()->getConnection());
        $connection->putWarmers();

        $warmers = $connection->getClient()->indices()->getWarmer(
            [
                'index' => $connection->getIndexName(),
                'name' => '*',
            ]
        );

        $this->assertNotEmpty($warmers[$connection->getIndexName()], 'Index should have warmers loaded.');

        $arguments['command'] = $command->getName();
        $commandTester->execute($arguments);
        $this->assertEquals($expected, $commandTester->getDisplay());

        $warmers = $connection->getClient()->indices()->getWarmer(
            [
                'index' => $connection->getIndexName(),
                'name' => '*',
            ]
        );

        $this->assertEmpty($warmers, 'Index should not have any warmers loaded.');
    }

    /**
     * @return WarmerDeleteCommand
     */
    private function getCommand()
    {
        $command = new WarmerDeleteCommand();
        $command->setContainer($this->getContainer());

        return $command;
    }
}
