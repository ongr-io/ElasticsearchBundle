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

use Elasticsearch\ClientBuilder;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param MetadataCollector $metadataCollector Metadata collector service.
     * @param Converter         $converter         Converter service to transform arrays to objects and visa versa.
     * @param LoggerInterface   $tracer
     * @param LoggerInterface   $logger
     */
    public function __construct($metadataCollector, $converter, $tracer = null, $logger = null)
    {
        $this->metadataCollector = $metadataCollector;
        $this->converter = $converter;
        $this->tracer = $tracer;
        $this->logger = $logger;
    }

    /**
     * @param EventDispatcherInterface   $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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

        if (!isset($connection['settings']['number_of_replicas'])) {
            $connection['settings']['number_of_replicas'] = 0;
        }

        if (!isset($connection['settings']['number_of_shards'])) {
            $connection['settings']['number_of_shards'] = 1;
        }

        $mappings = $this->metadataCollector->getClientMapping($managerConfig['mappings']);

        $client = ClientBuilder::create();
        $client->setHosts($connection['hosts']);
        $client->setTracer($this->tracer);

        if ($this->logger && $managerConfig['logger']['enabled']) {
            $client->setLogger($this->logger);
        }

        $indexSettings = [
            'index' => $connection['index_name'],
            'body' => array_filter(
                [
                    'settings' => $connection['settings'],
                    'mappings' => $mappings,
                ]
            ),
        ];

        $manager = new Manager(
            $managerName,
            $managerConfig,
            $client->build(),
            $indexSettings,
            $this->metadataCollector,
            $this->converter
        );

        $manager->setCommitMode($managerConfig['commit_mode']);
        $manager->setEventDispatcher($this->eventDispatcher);
        $manager->setBulkCommitSize($managerConfig['bulk_size']);

        return $manager;
    }
}
