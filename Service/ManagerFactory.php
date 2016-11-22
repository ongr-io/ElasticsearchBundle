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
use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Event\PostCreateManagerEvent;
use ONGR\ElasticsearchBundle\Event\PreCreateManagerEvent;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

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
     * @var Stopwatch
     */
    private $stopwatch;

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
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
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
        $managerAnalysis = [];
        $mappings = $this->metadataCollector->getClientMapping($managerConfig['mappings']);

        foreach ($mappings as $type) {
            $this->extractAnalysisFromProperties($type['properties'], $analysis, $managerAnalysis);
        }

        $connection['settings']['analysis'] = array_filter($managerAnalysis);

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

        $this->eventDispatcher &&
            $this->eventDispatcher->dispatch(
                Events::PRE_MANAGER_CREATE,
                new PreCreateManagerEvent($client, $indexSettings)
            );

        $manager = new Manager(
            $managerName,
            $managerConfig,
            $client->build(),
            $indexSettings,
            $this->metadataCollector,
            $this->converter
        );

        if (isset($this->stopwatch)) {
            $manager->setStopwatch($this->stopwatch);
        }

        $manager->setCommitMode($managerConfig['commit_mode']);
        $manager->setEventDispatcher($this->eventDispatcher);
        $manager->setBulkCommitSize($managerConfig['bulk_size']);

        $this->eventDispatcher &&
            $this->eventDispatcher->dispatch(Events::POST_MANAGER_CREATE, new PostCreateManagerEvent($manager));

        return $manager;
    }

    /**
     * Extracts analysis configuration from all the documents
     *
     * @param array $properties      Properties of a type or an object
     * @param array $analysis        The full analysis node from configuration
     * @param array $managerAnalysis The data that is being formed for the manager
     */
    private function extractAnalysisFromProperties($properties, $analysis, &$managerAnalysis)
    {
        foreach ($properties as $property) {
            if (isset($property['analyzer'])) {
                $analyzer = $analysis['analyzer'][$property['analyzer']];
                $managerAnalysis['analyzer'][$property['analyzer']] = $analyzer;

                $this->extractSubData('filter', $analyzer, $analysis, $managerAnalysis);
                $this->extractSubData('char_filter', $analyzer, $analysis, $managerAnalysis);
                $this->extractSubData('tokenizer', $analyzer, $analysis, $managerAnalysis);
            }

            if (isset($property['properties'])) {
                $this->extractAnalysisFromProperties($property['properties'], $analysis, $managerAnalysis);
            }
        }
    }

    /**
     * Extracts tokenizers and filters from analysis configuration
     *
     * @param string $type     Either filter or tokenizer
     * @param array  $analyzer The current analyzer
     * @param array  $analysis The full analysis node from configuration
     * @param array  $data     The data that is being formed for the manager
     */
    private function extractSubData($type, $analyzer, $analysis, &$data)
    {
        if (!isset($analyzer[$type])) {
            return;
        }

        if (is_array($analyzer[$type])) {
            foreach ($analyzer[$type] as $name) {
                if (isset($analysis[$type][$name])) {
                    $data[$type][$name] = $analysis[$type][$name];
                }
            }
        } else {
            $name = $analyzer[$type];

            if (isset($analysis[$type][$name])) {
                $data[$type][$name] = $analysis[$type][$name];
            }
        }
    }
}
