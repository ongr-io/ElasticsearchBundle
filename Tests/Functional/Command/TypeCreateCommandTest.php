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

use ONGR\ElasticsearchBundle\Command\TypeCreateCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests type create command execution.
 */
class TypeCreateCommandTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        // Do nothing.
    }

    /**
     * Data provider for testing type create command.
     *
     * @return array
     */
    public function getTestExecuteData()
    {
        return [
            [
                [],
                "Created `all` type(s) for manager named `default`.\n",
            ],
            [
                ['--type' => ['product', 'content']],
                "Created `product`, `content` type(s) for manager named `default`.\n",
            ],
            [
                ['--manager' => 'bar'],
                "Manager `bar` does not contain type(s) information.\n",
                false,
            ],
            [
                ['--manager' => 'bar', '--type' => ['category']],
                "Manager `bar` does not contain `category` type(s) information.\n",
                false,
            ],
        ];
    }

    /**
     * Test execute method on type create command.
     *
     * @param array  $arguments    Arguments pass to command.
     * @param string $message      Output message to assert.
     * @param bool   $checkMapping Set to false if mapping should not be checked.
     *
     * @dataProvider getTestExecuteData
     */
    public function testExecute($arguments, $message, $checkMapping = true)
    {
        $manager = array_key_exists('--manager', $arguments) ? $arguments['--manager'] : 'default';
        $connection = $this
            ->getContainer()
            ->get('es.manager.' . $manager)
            ->getConnection();
        $connection->dropAndCreateIndex(false, true);

        $this->assertEquals($message, $this->runCreateCommand($arguments));

        if ($checkMapping) {
            $this->assertTypes(
                array_key_exists('--type', $arguments) ? $arguments['--type'] : [],
                $manager
            );
        }

        $connection->dropIndex();
    }

    /**
     * Data provider for executing type create on existing types.
     *
     * @return array
     */
    public function getTestExecuteOnExistingTypesData()
    {
        return [
            [
                ['--type' => ['product', 'comment']],
                "Created `product`, `comment` type(s) for manager named `default`.\n",
                ['product', 'foocontent'],
            ],
            [
                ['--type' => ['product']],
                "ATTENTION: type(s) already loaded into `default` manager.\n",
                ['product', 'category'],
            ],
            [
                [],
                "ATTENTION: type(s) already loaded into `default` manager.\n",
            ],
            [
                ['--type' => ['product', 'comment']],
                "Created `product`, `comment` type(s) for manager named `default`.\n",
                ['product'],
            ],
        ];
    }

    /**
     * Tests executing create types command on existing types.
     *
     * @param array  $arguments
     * @param string $message
     * @param array  $createTypes
     *
     * @dataProvider getTestExecuteOnExistingTypesData
     */
    public function testExecuteOnExistingTypes($arguments, $message, $createTypes = [])
    {
        $manager = array_key_exists('--manager', $arguments) ? $arguments['--manager'] : 'default';
        $connection = $this
            ->getContainer()
            ->get('es.manager.' . $manager)
            ->getConnection();
        $connection->dropAndCreateIndex(false, true);
        $connection->createTypes($createTypes);

        $this->assertEquals($message, $this->runCreateCommand($arguments));

        $connection->dropIndex();
    }

    /**
     * Checks if types was created on client.
     *
     * @param array  $types   Types to check.
     * @param string $manager Manager name.
     */
    private function assertTypes($types, $manager = 'default')
    {
        $this->assertNotEmpty(
            $this->getManager($manager, false)->getConnection()->getMappingFromIndex($types),
            'Mapping should be created.'
        );
    }

    /**
     * Runs type create command.
     *
     * @param array $arguments Arguments or options pass to command.
     *
     * @return string Command output.
     */
    private function runCreateCommand(array $arguments = [])
    {
        $app = new Application();
        $command = new TypeCreateCommand();
        $command->setContainer($this->getContainer());
        $app->add($command);
        $cmd = $app->find('ongr:es:type:create');
        $tester = new CommandTester($cmd);
        $tester->execute(
            array_filter(
                array_replace(
                    [
                        'command' => $cmd->getName(),
                    ],
                    $arguments
                )
            )
        );

        return $tester->getDisplay();
    }
}
