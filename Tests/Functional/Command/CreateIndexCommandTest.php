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
                'bar',
                [
                    'timestamp' => false,
                    'warm' => false,
                ],
            ],
            [
                'default',
                [
                    'timestamp' => false,
                    'warm' => true,
                ],
            ],
        ];
    }

    /**
     * Tests creating index. Configuration from tests yaml.
     *
     * @param string $argument
     * @param array  $options
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecute($argument, $options)
    {
        $manager = $this->getManager($argument, false);

        $connection = $manager->getConnection();
        if ($connection->indexExists()) {
            $connection->dropIndex();
        }

        $app = new Application();
        $app->add($this->getCreateCommand());

        // Creates index.
        $command = $app->find('es:index:create');
        $commandTester = new CommandTester($command);
        $arguments = [
            'command' => $command->getName(),
            '--connection' => $argument,
        ];
        if ($options['timestamp']) {
            $arguments['--time'] = null;
        }
        if ($options['warm']) {
            $arguments['--with-warmers'] = null;
        }

        $commandTester->execute($arguments);

        $this->assertTrue($connection->indexExists(), 'Index should exist.');
        $connection->dropIndex();
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
