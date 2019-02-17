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

use ONGR\ElasticsearchBundle\Service\Manager;
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        foreach ($this->getDataArray() as $manager => $data) {
            // Create index and populate data
            $this->getManager($manager);
        }
    }

    /**
     * Can be overwritten in child class to populate elasticsearch index with the data.
     *
     * Example:
     *      "manager_name" =>
     *      [
     *          'type_name' => [
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
     * @param Manager $manager
     */
    private function ignoreVersions(Manager $manager)
    {
        $currentVersion = $manager->getVersionNumber();

        if (version_compare($currentVersion, 6, 'lt')) {
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
            $this->managers[$name]->dropIndex();
            unset($this->managers[$name]);
        }
    }

    /**
     * Populates elasticsearch with data.
     *
     * @param Manager $manager
     * @param array   $data
     */
    private function populateElasticsearchWithData($manager, array $data)
    {
        if (!empty($data)) {
            foreach ($data as $type => $documents) {
                foreach ($documents as $document) {
                    $manager->bulk('index', $type, $document);
                }
            }
            $manager->commit();
            $manager->refresh();
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
                $manager->dropIndex();
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
        if ($reinitialize) {
            static::bootKernel($kernelOptions);
        }

        return static::createClient()->getContainer();
    }

    /**
     * Returns manager instance with injected connection if does not exist creates new one.
     *
     * @param string $name Manager name
     *
     * @return Manager
     *
     * @throws \LogicException
     */
    protected function getManager($name = 'default')
    {
        $serviceName = sprintf('es.manager.%s', $name);

        // Looks for cached manager.
        if (array_key_exists($name, $this->managers)) {
            $this->ignoreVersions($this->managers[$name]);

            return $this->managers[$name];
        } elseif ($this->getContainer()->has($serviceName)) {
            /** @var Manager $manager */
            $manager = $this->getContainer()->get($serviceName);
            $this->managers[$name] = $manager;
        } else {
            throw new \LogicException(sprintf("Manager '%s' does not exist", $name));
        }

        $this->ignoreVersions($manager);
        $manager->dropAndCreateIndex();

        // Populates elasticsearch index with data
        $data = $this->getDataArray();
        if (!empty($data[$name])) {
            $this->populateElasticsearchWithData($manager, $data[$name]);
        }

        return $manager;
    }
}
