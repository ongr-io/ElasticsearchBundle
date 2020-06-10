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
use PackageVersions\Versions;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
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
        $mappings = $this->metadataCollector->getClientMapping($managerConfig['mappings']);

        $client = ClientBuilder::create();
        $client->setHosts($connection['hosts']);

        if ($this->tracer) {
            $client->setTracer($this->tracer);
        }

        if ($this->logger && $managerConfig['logger']['enabled']) {
            $client->setLogger($this->logger);
        }

        $indexSettings = [
            'index' => $connection['index_name'],
            'body' => array_filter(
                [
                    'settings' => array_merge(
                        $connection['settings'],
                        [
                            'analysis' =>
                                $this->metadataCollector->getClientAnalysis($managerConfig['mappings'], $analysis),
                        ]
                    ),
                    'mappings' => $mappings,
                ]
            ),
        ];

        if (class_exists(Versions::class)) {
            $elasticSearchVersion = explode('@', Versions::getVersion('elasticsearch/elasticsearch'))[0];
            if (0 === strpos($elasticSearchVersion, 'v')) {
                $elasticSearchVersion = substr($elasticSearchVersion, 1);
            }
            if (version_compare($elasticSearchVersion, '7.0.0', '>=')) {
                $indexSettings['include_type_name'] = true;
            }
        }

        $this->eventDispatcher &&
            $this->dispatch(
                Events::PRE_MANAGER_CREATE,
                $preCreateEvent = new PreCreateManagerEvent($client, $indexSettings)
            );

        $manager = new Manager(
            $managerName,
            $managerConfig,
            $client->build(),
            $preCreateEvent->getIndexSettings(),
            $this->metadataCollector,
            $this->converter
        );

        if (isset($this->stopwatch)) {
            $manager->setStopwatch($this->stopwatch);
        }

        $manager->setCommitMode($managerConfig['commit_mode']);
        $manager->setEventDispatcher($this->eventDispatcher);
        $manager->setCommitMode($managerConfig['commit_mode']);
        $manager->setBulkCommitSize($managerConfig['bulk_size']);

        $this->eventDispatcher &&
            $this->dispatch(Events::POST_MANAGER_CREATE, new PostCreateManagerEvent($manager));

        return $manager;
    }

    private function dispatch($eventName, $event)
    {
        if (class_exists(LegacyEventDispatcherProxy::class)) {
            return $this->eventDispatcher->dispatch($event, $eventName);
        } else {
            return $this->eventDispatcher->dispatch($eventName, $event);
        }
    }
}
