<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Test;

use Elasticsearch\Common\Exceptions\ElasticsearchException;
use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test which creates unique connection to test with.
 */
abstract class AbstractElasticsearchTestCase extends WebTestCase
{
    /**
     * @var Manager[] Holds used managers.
     */
    private $managers = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function runTest()
    {
        if ($this->getNumberOfRetries() < 1) {
            return parent::runTest();
        }

        foreach (range(1, $this->getNumberOfRetries()) as $try) {
            try {
                return parent::runTest();
            } catch (\Exception $e) {
                if (!($e instanceof ElasticsearchException)) {
                    throw $e;
                }
                // If error was from elasticsearch re-setup tests and retry.
                if ($try !== $this->getNumberOfRetries()) {
                    $this->tearDown();
                    $this->setUp();
                }
            }
        }

        throw $e;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->getContainer();
        $this->getManager();
    }

    /**
     * Returns number of retries tests should execute.
     *
     * @return int
     */
    protected function getNumberOfRetries()
    {
        return 0;
    }

    /**
     * Can be overwritten in child class to populate elasticsearch index with data.
     *
     * Example:
     *      "managername" =>
     *      [
     *          'acmetype' => [
     *              [
     *                  '_id' => 1,
     *                  'title' => 'foo',
     *              ],
     *              [
     *                  '_id' => 2,
     *                  'title' => 'bar',
     *              ]
     *          ]
     *      ]
     *
     * @return array
     */
    protected function getDataArray()
    {
        return [];
    }

    /**
     * Ignores versions specified.
     *
     * Returns two dimensional array, first item in sub array is version to ignore, second is comparator,
     * last test name. If no test name is provided it will be used on all test class.
     *
     * Comparator types can be found in `version_compare` documentation.
     *
     * Example: [
     *   ['1.2.7', '<='],
     *   ['1.2.9', '==', 'testSmth']
     * ]
     *
     * @return array
     */
    protected function getIgnoredVersions()
    {
        return [];
    }

    /**
     * Ignores version specified.
     *
     * @param Connection $connection
     */
    protected function ignoreVersions(Connection $connection)
    {
        $currentVersion = $connection->getVersionNumber();
        $ignore = null;

        foreach ($this->getIgnoredVersions() as $ignoredVersion) {
            if (version_compare($currentVersion, $ignoredVersion[0], $ignoredVersion[1]) === true) {
                $ignore = true;
                if (isset($ignoredVersion[2])) {
                    if ($ignoredVersion[2] === $this->getName()) {
                        break;
                    }
                    $ignore = false;
                }
            }
        }

        if ($ignore === true) {
            $this->markTestSkipped("Elasticsearch version {$currentVersion} not supported by this test.");
        }
    }

    /**
     * Removes manager from local cache and drops its index.
     *
     * @param string $name
     */
    protected function removeManager($name)
    {
        if (isset($this->managers[$name])) {
            $this->managers[$name]->getConnection()->dropIndex();
            unset($this->managers[$name]);
        }
    }

    /**
     * Populates elasticsearch with data.
     *
     * @param Manager $manager
     * @param array   $data
     */
    protected function populateElasticsearchWithData($manager, array $data)
    {
        if (!empty($data)) {
            foreach ($data as $type => $documents) {
                foreach ($documents as $document) {
                    $manager->getConnection()->bulk('index', $type, $document);
                }
            }
            $manager->commit();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->managers as $name => $manager) {
            try {
                $manager->getConnection()->dropIndex();
            } catch (\Exception $e) {
                // Do nothing.
            }
        }
    }

    /**
     * Returns service container.
     *
     * @param bool  $reinitialize  Force kernel reinitialization.
     * @param array $kernelOptions Options used passed to kernel if it needs to be initialized.
     *
     * @return ContainerInterface
     */
    protected function getContainer($reinitialize = false, $kernelOptions = [])
    {
        if (!$this->container || $reinitialize) {
            static::bootKernel($kernelOptions);
            $this->container = static::$kernel->getContainer();
        }

        return $this->container;
    }

    /**
     * Returns manager instance with injected connection if does not exist creates new one.
     *
     * @param string $name          Manager name.
     * @param bool   $createIndex   Create index or not.
     * @param array  $customMapping Custom index mapping config.
     *
     * @return Manager
     *
     * @throws \LogicException
     */
    protected function getManager($name = 'default', $createIndex = true, array $customMapping = [])
    {
        $serviceName = sprintf('es.manager.%s', $name);

        // Looks for cached manager.
        if (array_key_exists($name, $this->managers)) {
            $manager = $this->managers[$name];
        } elseif ($this->getContainer()->has($serviceName)) {
            /** @var Manager $manager */
            $manager = $this
                ->getContainer()
                ->get($serviceName);
            $this->managers[$name] = $manager;
        } else {
            throw new \LogicException(sprintf("Manager '%s' does not exist", $name));
        }

        $connection = $manager->getConnection();

        if ($connection instanceof Connection) {
            $this->ignoreVersions($connection);
        }

        // Updates settings.
        if (!empty($customMapping)) {
            $connection->updateSettings(['body' => ['mappings' => $customMapping]]);
        }

        // Drops and creates index.
        if ($createIndex) {
            $connection->dropAndCreateIndex();
        }

        // Populates elasticsearch index with data.
        $data = $this->getDataArray();
        if ($createIndex && isset($data[$name]) && !empty($data[$name])) {
            $this->populateElasticsearchWithData($manager, $data[$name]);
        }

        return $manager;
    }
}
