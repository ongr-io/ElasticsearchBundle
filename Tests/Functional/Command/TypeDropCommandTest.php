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

use ONGR\ElasticsearchBundle\Command\TypeDropCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests type drop command execution.
 */
class TypeDropCommandTest extends AbstractElasticsearchTestCase
{
    /**
     * Data provider for test execute method.
     *
     * @return array
     */
    public function getTestExecuteData()
    {
        return [
            [
                [],
                "Dropped `all` type(s) for manager named `default`.\n",
            ],
            [
                ['--force' => false],
                'ATTENTION: This action should not be used in production environment.'
                . "\n\nOption --force has to be used to drop type(s).\n",
                false,
            ],
            [
                ['--type' => ['product', 'category']],
                "Dropped `product`, `category` type(s) for manager named `default`.\n",
            ],
            [
                ['--type' => ['content'], '--manager' => 'bar'],
                "Manager `bar` does not contain `content` type(s) information.\n",
                false,
            ],
            [
                ['--manager' => 'bar'],
                "Manager `bar` does not contain type(s) information.\n",
                false,
            ],
        ];
    }

    /**
     * Tests command execute method.
     *
     * @param array  $arguments    Arguments pass to command.
     * @param string $message      Message outputed by command.
     * @param bool   $checkMapping Set to false if mapping should not be checked.
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecute($arguments, $message, $checkMapping = true)
    {
        $this->assertEquals($this->runDropCommand($arguments), $message);

        if ($checkMapping) {
            $this->assertTypes(
                array_key_exists('--type', $arguments) ? $arguments['--type'] : [],
                array_key_exists('--manager', $arguments) ? $arguments['--manager'] : 'default'
            );
        }
    }

    /**
     * Checks if types was created on client.
     *
     * @param array  $types   Types to check.
     * @param string $manager Manager name.
     */
    private function assertTypes($types, $manager = 'default')
    {
        $this->assertEmpty(
            $this->getManager($manager, false)->getConnection()->getMappingFromIndex($types),
            'Mappings should be deleted.'
        );
    }

    /**
     * Runs type drop command.
     *
     * @param array $arguments Arguments / options pass to command.
     *
     * @return string Command output.
     */
    private function runDropCommand($arguments)
    {
        $app = new Application();
        $command = new TypeDropCommand();
        $command->setContainer($this->getContainer());
        $app->add($command);
        $cmd = $app->find('ongr:es:type:drop');
        $tester = new CommandTester($cmd);
        $tester->execute(
            array_filter(
                array_replace(
                    [
                        'command' => $cmd->getName(),
                        '--force' => true,
                    ],
                    $arguments
                )
            )
        );

        return $tester->getDisplay();
    }
}
