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

use ONGR\ElasticsearchBundle\Command\WarmerPutCommand;
use ONGR\ElasticsearchBundle\Test\DelayedObjectWrapper;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests es warmer put command.
 */
class WarmerPutCommandTest extends ElasticsearchTestCase
{
    /**
     * @return array
     */
    public function getTestExecuteData()
    {
        return [
            [
                "All warmers have been put into manager named `default`\n",
                [],
            ],
            [
                "`test_foo_warmer` warmer(s) have been put into manager named `default`\n",
                [
                    'names' => ['test_foo_warmer'],
                ],
            ],
        ];
    }

    /**
     * Tests if warmers are being put into index if command is executed.
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
        $command = $app->find('ongr:es:warmer:put');
        $commandTester = new CommandTester($command);
        $connection = DelayedObjectWrapper::wrap($this->getManager()->getConnection());

        $warmers = $connection->getClient()->indices()->getWarmer(
            [
                'index' => $connection->getIndexName(),
                'name' => '*',
            ]
        );
        $this->assertEmpty($warmers, 'Index should not have any warmers loaded.');

        $arguments['command'] = $command->getName();
        $commandTester->execute($arguments);
        $this->assertEquals($expected, $commandTester->getDisplay());

        $warmers = $connection->getClient()->indices()->getWarmer(
            [
                'index' => $connection->getIndexName(),
                'name' => '*',
            ]
        );
        $this->assertNotEmpty($warmers[$connection->getIndexName()], 'Index should have warmers loaded.');
    }

    /**
     * @return WarmerPutCommand
     */
    private function getCommand()
    {
        $command = new WarmerPutCommand();
        $command->setContainer($this->getContainer());

        return $command;
    }
}
