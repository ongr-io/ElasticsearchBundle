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
     * @param MetadataCollector $metadataCollector Metadata collector service.
     * @param CacheProvider     $cache             Cache provider to save some data.
     */
    public function __construct($metadataCollector, $cache)
    {
        $this->metadataCollector = $metadataCollector;
        $this->cache = $cache;
    }

    /**
     * Factory function to create a manager instance.
     *
     * @param string $managerName   Manager name.
     * @param array  $connection    Conenction configuration.
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

        $indexSettings = [
            'index' => $connection['index_name'],
            'body' => [
                'settings' => $connection['settings'],
                'mappings' => $mappings,
            ],
        ];

        $manager = new Manager($client->build(), $indexSettings);

        return $manager;
    }
}
