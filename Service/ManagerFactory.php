<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

use Doctrine\Common\Cache\CacheProvider;
use Elasticsearch\ClientBuilder;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use Psr\Log\LoggerInterface;

/**
 * Elasticsearch Manager factory class.
 */
class ManagerFactory
{
    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LoggerInterface
     */
    private $tracer;

    /**
     * @param MetadataCollector $metadataCollector Metadata collector service.
     * @param CacheProvider     $cache             Cache provider to save some data.
     * @param Converter         $converter         Converter service to transform arrays to objects and visa versa.
     * @param LoggerInterface   $tracer
     * @param LoggerInterface   $logger
     */
    public function __construct($metadataCollector, $cache, $converter, $tracer = null, $logger = null)
    {
        $this->metadataCollector = $metadataCollector;
        $this->cache = $cache;
        $this->converter = $converter;
        $this->tracer = $tracer;
        $this->logger = $logger;
    }

    /**
     * Factory function to create a manager instance.
     *
     * @param string $managerName   Manager name.
     * @param array  $connection    Connection configuration.
     * @param array  $analysis      Analyzers, filters and tokenizers config.
     * @param array  $managerConfig Manager configuration.
     *
     * @return Manager
     */
    public function createManager($managerName, $connection, $analysis, $managerConfig)
    {
        foreach (array_keys($analysis) as $analyzerType) {
            foreach ($connection['analysis'][$analyzerType] as $name) {
                $connection['settings']['analysis'][$analyzerType][$name] = $analysis[$analyzerType][$name];
            }
        }
        unset($connection['analysis']);

        $mappings = $this->metadataCollector->getClientMapping($managerConfig);

        $client = ClientBuilder::create();

        $client->setHosts($connection['hosts']);

        if ($this->tracer && $managerConfig['profiler']) {
            $client->setTracer($this->tracer);
        }

        if ($this->logger && $managerConfig['logger']['enabled']) {
            $client->setLogger($this->logger);
        }

        $indexSettings = [
            'index' => $connection['index_name'],
            'body' => [
                'settings' => $connection['settings'],
                'mappings' => $mappings,
            ],
        ];

        $manager = new Manager(
            $managerName,
            $managerConfig,
            $client->build(),
            $indexSettings,
            $this->metadataCollector,
            $this->converter
        );

        return $manager;
    }
}
