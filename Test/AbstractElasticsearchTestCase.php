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

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test which creates unique connection to test with.
 */
abstract class AbstractElasticsearchTestCase extends WebTestCase
{
    protected static $cachedContainer;

    /**
     * @var IndexService[]
     */
    private $indexes = [];

    //You may use setUp() for your personal needs.
    protected function setUp()
    {
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
    protected function getDataArray(): array
    {
        return [];
    }

    private function populateElastic(IndexService $indexService, array $documents = [])
    {
        foreach ($documents as $document) {
            $indexService->bulk('index', $document);
        }
        $indexService->commit();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
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

    protected function getContainer($reinitialize = false, $kernelOptions = []): ContainerInterface
    {
        if (!self::$cachedContainer && !$reinitialize) {
//            static::bootKernel($kernelOptions);

            self::$cachedContainer = static::createClient(['environment' => 'test'])->getContainer();
        }

        return self::$cachedContainer;
    }

    protected function getIndex($namespace, $createIndex = true): IndexService
    {
        try {
            if (!array_key_exists($namespace, $this->indexes)) {
                $this->indexes[$namespace] = $this->getContainer()->get($namespace);
            }

            if (!$this->indexes[$namespace]->indexExists() && $createIndex) {
                $this->indexes[$namespace]->dropAndCreateIndex();

                // Populates elasticsearch index with the data
                $data = $this->getDataArray();
                if (!empty($data[$namespace])) {
                    $this->populateElastic($this->indexes[$namespace], $data[$namespace]);
                }
                $this->indexes[$namespace]->refresh();
            }

            return $this->indexes[$namespace];
        } catch (\Exception $e) {
            throw new \LogicException($e->getMessage());
        }
    }
}
