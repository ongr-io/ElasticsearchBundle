<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Client;

use Elasticsearch\Client;
use ONGR\ElasticsearchBundle\Mapping\MappingTool;

/**
 * This class interacts with elasticsearch using injected client.
 */
class Connection
{
    /**
     * @var Client
     */
    private $client;

    /**
     * Holds index information. Similar structure to elasticsearch docs.
     *
     * Example:
     *
     * ```php
     * array(
     *      'index' => 'index name'
     *      'body' => [
     *          'settings' => [...],
     *          'mappings' => [...]
     *      ]
     * )
     * ```
     *
     * @var array
     */
    private $settings;

    /**
     * Container for bulk queries.
     *
     * @var array
     */
    private $bulkQueries;

    /**
     * Holder for consistency, refresh and replication parameters.
     *
     * @var array
     */
    private $bulkParams;

    /**
     * Construct.
     *
     * @param Client $client   Elasticsearch client.
     * @param array  $settings Settings array.
     */
    public function __construct($client, $settings)
    {
        $this->client = $client;
        $this->settings = $settings;
        $this->bulkQueries = [];
        $this->bulkParams = [];
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Adds query to bulk queries container.
     *
     * @param string       $operation One of: index, update, delete, create.
     * @param string|array $type      Elasticsearch type name.
     * @param array        $query     DSL to execute.
     *
     * @throws \InvalidArgumentException
     */
    public function bulk($operation, $type, array $query)
    {
        switch ($operation) {
            case 'index':
            case 'create':
            case 'update':

                $this->bulkQueries['body'][] = [
                    $operation => [
                        '_index' => $this->getIndexName(),
                        '_type' => $type,
                        '_id' => isset($query['_id']) ? $query['_id'] : null,
                        '_ttl' => isset($query['_ttl']) ? $query['_ttl'] : null,
                        '_parent' => isset($query['_parent']) ? $query['_parent'] : null
                    ]
                ];

                // Unset reserved keys.
                unset($query['_id'], $query['_ttl'], $query['_parent']);

                $this->bulkQueries['body'][] = $query;
                break;
            default:
                throw new \InvalidArgumentException('Wrong bulk operation selected');
        }
    }

    /**
     * Optional setter to change bulk query params.
     *
     * @param array $params Possible keys:
     *                      ['consistency'] = (enum) Explicit write consistency setting for the operation.
     *                      ['refresh']     = (boolean) Refresh the index after performing the operation.
     *                      ['replication'] = (enum) Explicitly set the replication type.
     */
    public function setBulkParams(array $params)
    {
        $this->bulkParams = $params;
    }

    /**
     * Flushes the current query container to the index, used for bulk queries execution.
     */
    public function commit()
    {
        $this->bulkQueries = array_merge($this->bulkQueries, $this->bulkParams);
        $this->client->bulk($this->bulkQueries);
        $this->client->indices()->flush();

        $this->bulkQueries = [];
    }

    /**
     * Send refresh call to index.
     */
    public function refresh()
    {
        $this->client->indices()->refresh();
    }

    /**
     * Send refresh call to index.
     */
    public function flush()
    {
        $this->client->indices()->flush();
    }

    /**
     * Executes search query in the index.
     *
     * @param array $types             List of types to search in.
     * @param array $query             Query to execute.
     * @param array $queryStringParams Query parameters.
     *
     * @return array
     */
    public function search(array $types, array $query, array $queryStringParams = [])
    {
        $params['index'] = $this->getIndexName();
        $params['type'] = implode(',', $types);
        $params['body'] = $query;

        if (!empty($queryStringParams)) {
            $params = array_merge($queryStringParams, $params);
        }

        return $this->client->search($params);
    }

    /**
     * Execute scrolled search.
     *
     * @param string $scrollId       Scroll id.
     * @param string $scrollDuration Specify how long a consistent view of the index should be maintained
     *                               for scrolled search.
     *
     * @return array
     */
    public function scroll($scrollId, $scrollDuration)
    {
        $params['scroll_id'] = $scrollId;
        $params['scroll'] = $scrollDuration;

        return $this->client->scroll($params);
    }

    /**
     * Creates fresh elasticsearch index.
     */
    public function createIndex()
    {
        $this->client->indices()->create($this->settings);
    }

    /**
     * Drops elasticsearch index.
     */
    public function dropIndex()
    {
        $this->client->indices()->delete(['index' => $this->getIndexName()]);
    }

    /**
     * Tries to drop and create fresh elasticsearch index.
     */
    public function dropAndCreateIndex()
    {
        try {
            $this->dropIndex();
        } catch (\Exception $e) {
            // Do nothing because I'm only trying.
        }

        $this->createIndex();
    }

    /**
     * Checks if connection index is already created.
     *
     * @return bool
     */
    public function indexExists()
    {
        return $this->client->indices()->exists(['index' => $this->getIndexName()]);
    }

    /**
     * Returns index name this connection is attached to.
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->settings['index'];
    }

    /**
     * Sets index name this connection is attached to.
     *
     * @param string $name
     */
    public function setIndexName($name)
    {
        $this->settings['index'] = $name;
    }

    /**
     * Returns mapping by type.
     *
     * @param string $type Type name.
     *
     * @return array|null
     */
    public function getMapping($type)
    {
        if (array_key_exists($type, $this->settings['body']['mappings'])) {
            return $this->settings['body']['mappings'][$type];
        }

        return null;
    }

    /**
     * Sets whole mapping, deleting non-existent types.
     *
     * @param array $mapping Mapping structure to force.
     */
    public function forceMapping(array $mapping)
    {
        $this->settings['body']['mappings'] = $mapping;
    }

    /**
     * Sets mapping by type.
     *
     * @param string $type    Type name.
     * @param array  $mapping Mapping structure.
     */
    public function setMapping($type, array $mapping)
    {
        $this->settings['body']['mappings'][$type] = $mapping;
    }

    /**
     * Mapping is compared with loaded, if needed updates it and returns true.
     *
     * @return bool
     * @throws \LogicException
     */
    public function updateMapping()
    {
        if (!isset($this->settings['body']['mappings']) || empty($this->settings['body']['mappings'])) {
            throw new \LogicException('Connection does not have any mapping loaded.');
        }

        $newMapping = $this->settings['body']['mappings'];
        $indexName = $this->getIndexName();
        $oldMapping = $this
            ->client
            ->indices()
            ->getMapping(['index' => $indexName]);
        $tool = new MappingTool();
        $updated = false;
        $quick = empty($oldMapping);
        if (!$quick) {
            $oldMapping = $oldMapping[$indexName]['mappings'];
        }

        // Find out which types don't exist anymore.
        $typeDiff = array_diff_key($oldMapping, $newMapping);
        foreach ($typeDiff as $oldTypeName => $oldType) {
            $this->client->indices()->deleteMapping(
                [
                    'index' => $indexName,
                    'type' => $oldTypeName,
                ]
            );
            $updated = true;
        }

        // Search for differences in types.
        foreach ($newMapping as $type => $properties) {
            $diff = null;
            if (!$quick && array_key_exists($type, $oldMapping)) {
                $tool->setMapping($properties);
                $diff = $tool->symDifference($oldMapping[$type]);
            }

            if ($diff !== [] || $diff === null || $quick) {
                $this->client->indices()->putMapping(
                    [
                        'index' => $indexName,
                        'type' => $type,
                        'body' => [
                            $type => $properties
                        ]
                    ]
                );
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Updates index settings recursive.
     *
     * @param array $settings Settings.
     * @param bool  $force    Should replace structure instead of merging.
     */
    public function updateSettings(array $settings, $force = false)
    {
        if ($force) {
            $this->settings = $settings;
        } else {
            $this->settings = array_replace_recursive($this->settings, $settings);
        }
    }
}
