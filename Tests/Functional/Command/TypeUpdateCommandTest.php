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

use ONGR\ElasticsearchBundle\Command\TypeUpdateCommand;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for type update command.
 */
class TypeUpdateCommandTest extends AbstractElasticsearchTestCase
{
    /**
     * @var string
     */
    private $documentDir;

    /**
     * @var string
     */
    private $file;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Set up custom document to test mapping with.
        $this->documentDir = $this->getContainer()->get('kernel')->locateResource('@AcmeTestBundle/Document/');
        $this->file = $this->documentDir . 'Article.php';
    }

    /**
     * Check if update works as expected.
     */
    public function testExecute()
    {
        $this->assertMappingNotSet("Article mapping shouldn't be defined yet.");
        copy($this->documentDir . 'documentSample.txt', $this->file);

        // Reinitialize container.
        $this->getContainer(true, ['environment' => 'test2']);

        $this->assertEquals(
            $this->runUpdateCommand(),
            "`all` type(s) have been updated for manager named `default`.\n"
        );
        $this->assertMappingSet('Article mapping should be defined after update.');
    }

    /**
     * Check if updating works with type selected.
     */
    public function testExecuteType()
    {
        $this->assertMappingNotSet("Article mapping shouldn't be defined yet.");
        copy($this->documentDir . 'documentSample.txt', $this->file);

        // Reinitialize container.
        $this->getContainer(true, ['environment' => 'test2']);

        $this->assertEquals(
            $this->runUpdateCommand(['--type' => ['product']]),
            "`product` type(s) are already up to date for manager named `default`.\n"
        );
        $this->assertMappingNotSet("Article mapping shouldn't be defined, type selected was `product`.");

        $this->assertEquals(
            $this->runUpdateCommand(['--type' => ['article']]),
            "`article` type(s) have been updated for manager named `default`.\n"
        );
        $this->assertMappingSet('Article mapping should be defined after update, type selected was `article`.');
    }

    /**
     * Check if up to date mapping check works.
     */
    public function testExecuteUpdated()
    {
        $this->assertEquals(
            "`all` type(s) are already up to date for manager named `default`.\n",
            $this->runUpdateCommand()
        );
        $this->assertMappingNotSet("Article was never added, type shouldn't be added.");
    }

    /**
     * Data provider for testing update command with manager w/o mapping.
     *
     * @return array
     */
    public function getTestUpdateCommandMessagesData()
    {
        return [
            [
                ['--manager' => 'bar'],
                "No mapping was found in `bar` manager.\n",
            ],
            [
                ['--manager' => 'bar', '--type' => ['product']],
                "No mapping was found for `product` types in `bar` manager.\n",
            ],
            [
                ['--force' => false],
                'ATTENTION: This action should not be used in production environment.'
                . "\n\nOption --force has to be used to drop type(s).\n",
            ],
        ];
    }

    /**
     * Tests update command output.
     *
     * @param array  $arguments Arguments to pass to command.
     * @param string $message   Output message.
     *
     * @dataProvider getTestUpdateCommandMessagesData
     */
    public function testUpdateCommandMessages($arguments, $message)
    {
        $this->assertEquals($this->runUpdateCommand($arguments), $message);
    }

    /**
     * Asserts mapping is set and correct.
     *
     * @param string $message
     */
    private function assertMappingSet($message)
    {
        $mapping = $this
            ->getContainer()
            ->get('es.manager.default')
            ->getConnection()
            ->getMappingFromIndex('article');

        $this->assertNotEmpty($mapping, $message);
        $expectedMapping = [
            'properties' => [
                'title' => ['type' => 'string'],
            ],
        ];
        $this->assertEquals($expectedMapping, $mapping);
    }

    /**
     * Asserts mapping isn't set.
     *
     * @param string $message Assert message.
     */
    private function assertMappingNotSet($message)
    {
        $this->assertEmpty(
            $this->getContainer()->get('es.manager.default')->getConnection()->getMappingFromIndex('article'),
            $message
        );
    }

    /**
     * Runs update command.
     *
     * @param array $arguments Arguments/options to pass to command.
     *
     * @return string Command display.
     */
    private function runUpdateCommand($arguments = [])
    {
        $command = new TypeUpdateCommand();
        $command->setContainer($this->getContainer());

        $app = new Application();
        $app->add($command);

        $commandToTest = $app->find('ongr:es:type:update');
        $commandTester = new CommandTester($commandToTest);

        $result = $commandTester->execute(
            array_filter(
                array_replace(
                    [
                        'command' => $commandToTest->getName(),
                        '--force' => true,
                    ],
                    $arguments
                )
            )
        );

        return $commandTester->getDisplay();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        @unlink($this->file);
    }
}
