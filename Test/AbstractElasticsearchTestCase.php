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

use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test which creates unique connection to test with.
 */
abstract class AbstractElasticsearchTestCase extends WebTestCase
{
    /**
     * @var IndexService[]
     */
    private $indexes = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        #make sure test data is inserted to teh ES
    }

    /**
     * Can be overwritten in child class to populate elasticsearch index with the data.
     *
     * Example:
     *      "/This/Should/Be/Index/Document/Namespace" =>
     *      [
     *          '_doc' => [
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

    private function populateElasticsearchWithData(IndexService $indexService, array $data)
    {
        if (!empty($data)) {
            foreach ($data as $type => $documents) {
                foreach ($documents as $document) {
                    $indexService->bulk('index', $type, $document);
                }
            }
            $indexService->commit();
            $indexService->refresh();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->indexes as $name => $index) {
            try {
                $index->dropIndex();
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

    protected function getIndex($namespace): IndexService
    {
        if (!array_key_exists($namespace, $this->indexes) && $this->getContainer()->has($namespace)) {
            $this->indexes[$namespace] = $this->getContainer()->get($namespace);
        } else {
            throw new \LogicException(sprintf("Manager '%s' does not exist", $namespace));
        }
        $this->indexes[$namespace]->dropAndCreateIndex();

        // Populates elasticsearch index with data
        $data = $this->getDataArray();
        if (!empty($data[$namespace])) {
            $this->populateElasticsearchWithData($this->indexes[$namespace], $data[$namespace]);
        }

        return $this->indexes[$namespace];
    }
}
