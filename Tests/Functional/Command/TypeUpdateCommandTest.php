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
use ONGR\ElasticsearchBundle\Command\TypeUpdateCommand;
use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Functional tests for type update command.
 */
class TypeUpdateCommandTest extends WebTestCase
{
    /**
     * @var string
     */
    private $documentDir;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var string
     */
    private $file;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        // Only a single instance of container should be used on all commands and throughout the test.
        $this->container = self::createClient()->getContainer();
        $this->manager = $this->container->get('es.manager');

        // Set up custom document to test mapping with.
        $this->documentDir = $this->container->get('kernel')->locateResource('@AcmeTestBundle/Document/');
        $this->file = $this->documentDir . 'Article.php';

        // Create index for testing.
        $this->app = new Application();
        $this->createIndexCommand();
    }

    /**
     * Check if update works as expected.
     */
    public function testExecute()
    {
        $this->assertMappingNotSet("Article mapping shouldn't be defined yet.");

        copy($this->documentDir . 'documentSample.txt', $this->file);

        $this->runUpdateCommand();
        $this->assertMappingSet('Article mapping should be defined after update.');
    }

    /**
     * Check if updating works with type selected.
     */
    public function testExecuteType()
    {
        $this->assertMappingNotSet("Article mapping shouldn't be defined yet.");

        copy($this->documentDir . 'documentSample.txt', $this->file);

        $this->runUpdateCommand('product');
        $this->assertMappingNotSet("Article mapping shouldn't be defined, type selected was `product`.");

        $this->runUpdateCommand('article');
        $this->assertMappingSet('Article mapping should be defined after update, type selected was `article`.');
    }

    /**
     * Check if up to date mapping check works.
     */
    public function testExecuteUpdated()
    {
        $this->assertStringStartsWith('Types are already up to date.', $this->runUpdateCommand());
        $this->assertMappingNotSet("Article was never added, type shouldn't be added.");
    }

    /**
     * Asserts mapping is set and correct.
     *
     * @param string $message
     */
    protected function assertMappingSet($message)
    {
        $mapping = $this->manager->getConnection()->getMapping('article');
        $this->assertNotNull($mapping, $message);
        $expectedMapping = [
            'properties' => [
                'title' => ['type' => 'string']
            ]
        ];
        $this->assertEquals($expectedMapping, $mapping);
    }

    /**
     * Asserts mapping isn't set.
     *
     * @param string $message
     */
    protected function assertMappingNotSet($message)
    {
        $this->assertNull($this->manager->getConnection()->getMapping('article'), $message);
    }

    /**
     * Runs update command.
     *
     * @param string $type
     *
     * @return string
     */
    protected function runUpdateCommand($type = '')
    {
        $command = new TypeUpdateCommand();
        $command->setContainer($this->container);

        $this->app->add($command);
        $commandToTest = $this->app->find('es:type:update');
        $commandTester = new CommandTester($commandToTest);

        $result = $commandTester->execute(
            [
                'command' => $commandToTest->getName(),
                '--force' => true,
                '--type' => $type,
            ]
        );

        $this->assertEquals(0, $result, "Mapping update wasn't executed successfully.");

        return $commandTester->getDisplay();
    }

    /**
     * Creates index for testing.
     *
     * @param string $manager
     */
    protected function createIndexCommand($manager = 'default')
    {
        $command = new IndexCreateCommand();
        $command->setContainer($this->container);

        $this->app->add($command);
        $command = $this->app->find('es:index:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--manager' => $manager,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        try {
            $this->manager->getConnection()->dropIndex();
        } catch (Exception $ex) {
            // Index wasn't actually created.
        }
        @unlink($this->file);
    }
}
