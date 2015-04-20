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
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use ONGR\ElasticsearchBundle\Cache\WarmerInterface;
use ONGR\ElasticsearchBundle\Cache\WarmersContainer;
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
     * @var array Container for bulk queries.
     */
    private $bulkQueries;

    /**
     * @var array Holder for consistency, refresh and replication parameters.
     */
    private $bulkParams;

    /**
     * @var WarmersContainer
     */
    private $warmers;

    /**
     * @var bool
     */
    private $readOnly;

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
        $this->warmers = new WarmersContainer();
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
        $this->isReadOnly('Bulk');

        if (!in_array($operation, ['index', 'create', 'update', 'delete'])) {
            throw new \InvalidArgumentException('Wrong bulk operation selected');
        }

        $this->bulkQueries['body'][] = [
            $operation => array_filter(
                [
                    '_index' => $this->getIndexName(),
                    '_type' => $type,
                    '_id' => isset($query['_id']) ? $query['_id'] : null,
                    '_ttl' => isset($query['_ttl']) ? $query['_ttl'] : null,
                    '_parent' => isset($query['_parent']) ? $query['_parent'] : null,
                ]
            ),
        ];
        unset($query['_id'], $query['_ttl'], $query['_parent']);

        switch ($operation) {
            case 'index':
            case 'create':
                $this->bulkQueries['body'][] = $query;
                break;
            case 'update':
                $this->bulkQueries['body'][] = ['doc' => $query];
                break;
            case 'delete':
                // Body for delete operation is not needed to apply.
            default:
                // Do nothing.
                break;
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
     *
     * Makes your documents available for search.
     */
    public function refresh()
    {
        $this->client->indices()->refresh();
    }

    /**
     * Send flush call to index.
     *
     * Causes a Lucene commit to happen (more expensive than refresh).
     */
    public function flush()
    {
        $this->client->indices()->flush();
    }

    /**
     * Removes a single document.
     *
     * @param array $params Parameters.
     *
     * $params = [
     *   'index' => 'index_name',
     *   'type' => 'document_type',
     *   'id' => 'id',
     *   ];.
     *
     * @return array
     */
    public function delete($params)
    {
        $this->isReadOnly('Delete');

        return $this->client->delete($params);
    }

    /**
     * Delete by query.
     *
     * @param array $types List of types to search in.
     * @param array $query Query to execute.
     *
     * @return array
     */
    public function deleteByQuery(array $types, array $query)
    {
        $params = [];
        $params['index'] = $this->getIndexName();
        $params['type'] = implode(',', $types);
        $params['body'] = $query;

        return $this->client->deleteByQuery($params);
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
        $params = [];
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
        $params = [];
        $params['scroll_id'] = $scrollId;
        $params['scroll'] = $scrollDuration;

        return $this->client->scroll($params);
    }

    /**
     * Creates fresh elasticsearch index.
     *
     * @param bool $putWarmers Determines if warmers should be loaded.
     * @param bool $noMapping  Determines if mapping should be included.
     */
    public function createIndex($putWarmers = false, $noMapping = false)
    {
        $this->isReadOnly('Create index');

        $settings = $this->settings;

        if ($noMapping) {
            unset($settings['body']['mappings']);
        }
        $this->client->indices()->create($settings);

        if ($putWarmers) {
            // Sometimes Elasticsearch gives service unavailable.
            usleep(200000);
            $this->putWarmers();
        }
    }

    /**
     * Drops elasticsearch index.
     */
    public function dropIndex()
    {
        $this->isReadOnly('Drop index');

        $this->client->indices()->delete(['index' => $this->getIndexName()]);
    }

    /**
     * Puts mapping into elasticsearch client.
     *
     * @param array $types Specific types to put.
     *
     * @return int
     */
    public function createTypes(array $types = [])
    {
        $this->isReadOnly('Create types');

        $mapping = $this->getMapping($types);
        if (empty($mapping)) {
            return 0;
        }

        $mapping = array_diff_key($mapping, $this->getMappingFromIndex($types));
        if (empty($mapping)) {
            return -1;
        }

        $this->loadMappingArray($mapping);

        return 1;
    }

    /**
     * Drops mapping from elasticsearch client.
     *
     * @param array $types Specific types to drop.
     *
     * @return int
     */
    public function dropTypes(array $types = [])
    {
        $this->isReadOnly('Drop types');

        $mapping = $this->getMapping($types);

        if (empty($mapping)) {
            return 0;
        }

        $this->unloadMappingArray(array_keys($mapping));

        return 1;
    }

    /**
     * Updates elasticsearch client mapping.
     *
     * @param array $types Specific types to update.
     *
     * @return int
     */
    public function updateTypes(array $types = [])
    {
        $this->isReadOnly('Update types');

        if (!$this->getMapping($types)) {
            return -1;
        }

        $tempSettings = $this->settings;
        $tempSettings['index'] = uniqid('mapping_check_');
        $mappingCheckConnection = new Connection($this->client, $tempSettings);
        $mappingCheckConnection->createIndex();
        $mappingCheckConnection->createTypes($types);

        $newMapping = $mappingCheckConnection->getMappingFromIndex($types);
        $oldMapping = $this->getMappingFromIndex($types);

        $mappingCheckConnection->dropIndex();

        $tool = new MappingTool();
        $updated = (int)$tool->checkMapping($oldMapping, $newMapping);

        if ($updated) {
            $this->unloadMappingArray($tool->getRemovedTypes());
            $this->loadMappingArray($tool->getUpdatedTypes());
        }

        return $updated;
    }

    /**
     * Tries to drop and create fresh elasticsearch index.
     *
     * @param bool $putWarmers Determines if warmers should be loaded.
     * @param bool $noMapping  Determines if mapping should be included.
     */
    public function dropAndCreateIndex($putWarmers = false, $noMapping = false)
    {
        try {
            $this->dropIndex();
        } catch (\Exception $e) {
            // Do nothing because I'm only trying.
        }

        $this->createIndex($putWarmers, $noMapping);
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
     * Returns mapping by type if defined.
     *
     * @param string|array $type Type names.
     *
     * @return array|null
     */
    public function getMapping($type = [])
    {
        if (isset($this->settings['body']['mappings'])) {
            return $this->filterMapping($type, $this->settings['body']['mappings']);
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
     * Sets multiple mappings.
     *
     * @param array $mapping Mapping to set.
     * @param bool  $cleanUp Cleans currect mapping.
     */
    public function setMultipleMapping(array $mapping, $cleanUp = false)
    {
        if ($cleanUp === true) {
            unset($this->settings['body']['mappings']);
        }

        foreach ($mapping as $type => $map) {
            $this->setMapping($type, $map);
        }
    }

    /**
     * Mapping is compared with loaded, if needed updates it and returns true.
     *
     * @param array $types Types to update.
     *
     * @return bool
     *
     * @throws \LogicException
     *
     * @deprecated Will be removed in 1.0. Please now use Connection#updateTypes()
     */
    public function updateMapping(array $types = [])
    {
        return $this->updateTypes($types);
    }

    /**
     * Closes index.
     */
    public function close()
    {
        $this->isReadOnly('Close index');

        $this->getClient()->indices()->close(['index' => $this->getIndexName()]);
    }

    /**
     * Returns whether the index is opened.
     *
     * @return bool
     */
    public function isOpen()
    {
        try {
            $this->getClient()->indices()->recovery(['index' => $this->getIndexName()]);
        } catch (Forbidden403Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Opens index.
     */
    public function open()
    {
        $this->isReadOnly('Open index');

        $this->getClient()->indices()->open(['index' => $this->getIndexName()]);
    }

    /**
     * Returns mapping from index.
     *
     * @param array|string $types Returns only certain set of types if set.
     *
     * @return array
     */
    public function getMappingFromIndex($types = [])
    {
        $mapping = $this
            ->client
            ->indices()
            ->getMapping(['index' => $this->getIndexName()]);

        if (array_key_exists($this->getIndexName(), $mapping)) {
            return $this->filterMapping($types, $mapping[$this->getIndexName()]['mappings']);
        }

        return [];
    }

    /**
     * Returns Elasticsearch version number.
     *
     * @return string
     */
    public function getVersionNumber()
    {
        return $this->client->info()['version']['number'];
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

    /**
     * Clears elasticsearch client cache.
     */
    public function clearCache()
    {
        $this->isReadOnly('Clear cache');

        $this->client->indices()->clearCache(['index' => $this->getIndexName()]);
    }

    /**
     * Adds warmer to conatiner.
     *
     * @param WarmerInterface $warmer
     */
    public function addWarmer(WarmerInterface $warmer)
    {
        $this->warmers->addWarmer($warmer);
    }

    /**
     * Loads warmers into elasticseach.
     *
     * @param array $names Warmers names to put.
     *
     * @return bool
     */
    public function putWarmers(array $names = [])
    {
        return $this->warmersAction('put', $names);
    }

    /**
     * Deletes warmers from elasticsearch index.
     *
     * @param array $names Warmers names to delete.
     *
     * @return bool
     */
    public function deleteWarmers(array $names = [])
    {
        return $this->warmersAction('delete', $names);
    }

    /**
     * Set connection to read only state.
     *
     * @param bool $readOnly
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;
    }

    /**
     * Checks if connection is read only.
     *
     * @param string $message Error message.
     *
     * @throws Forbidden403Exception
     */
    public function isReadOnly($message = '')
    {
        if ($this->readOnly) {
            throw new Forbidden403Exception("Manager is readonly! {$message} operation not permitted.");
        }
    }

    /**
     * Executes warmers actions.
     *
     * @param string $action Action name.
     * @param array  $names  Warmers names.
     *
     * @return bool
     *
     * @throws \LogicException
     */
    private function warmersAction($action, $names = [])
    {
        $this->isReadOnly('Warmer edit');

        $status = false;
        $warmers = $this->warmers->getWarmers();
        $this->validateWarmers($names, array_keys($warmers));

        foreach ($warmers as $name => $body) {
            if (empty($names) || in_array($name, $names)) {
                switch ($action) {
                    case 'put':
                        $this->getClient()->indices()->putWarmer(
                            [
                                'index' => $this->getIndexName(),
                                'name' => $name,
                                'body' => $body,
                            ]
                        );
                        break;
                    case 'delete':
                        $this->getClient()->indices()->deleteWarmer(
                            [
                                'index' => $this->getIndexName(),
                                'name' => $name,
                            ]
                        );
                        break;
                    default:
                        throw new \LogicException('Unknown warmer action');
                }
            }

            $status = true;
        }

        return $status;
    }

    /**
     * Warmer names validation.
     *
     * @param array $names       Names to check.
     * @param array $warmerNames Warmer names loaded.
     *
     * @throws \RuntimeException
     */
    private function validateWarmers($names, $warmerNames = [])
    {
        if (empty($warmerNames)) {
            $warmerNames = array_keys($this->warmers->getWarmers());
        }

        $unknown = array_diff($names, $warmerNames);

        if (!empty($unknown)) {
            throw new \RuntimeException(
                'Warmer(s) named ' . implode(', ', $unknown)
                . ' do not exist. Available: ' . implode(', ', $warmerNames)
            );
        }
    }

    /**
     * Puts mapping into elasticsearch.
     *
     * @param array $mapping Mapping to put into client.
     */
    private function loadMappingArray(array $mapping)
    {
        foreach ($mapping as $type => $properties) {
            $this->client->indices()->putMapping(
                [
                    'index' => $this->getIndexName(),
                    'type' => $type,
                    'body' => [
                        $type => $properties,
                    ],
                ]
            );
        }
    }

    /**
     * Drops mapping from elasticsearch client.
     *
     * @param array $mapping Mapping to drop from client.
     */
    private function unloadMappingArray(array $mapping)
    {
        foreach ($mapping as $type) {
            $this->client->indices()->deleteMapping(
                [
                    'index' => $this->getIndexName(),
                    'type' => $type,
                ]
            );
        }
    }

    /**
     * Filters out mapping from given type.
     *
     * @param string|array $type    Types to filter from mapping.
     * @param array        $mapping Mapping array.
     *
     * @return array
     */
    private function filterMapping($type, $mapping)
    {
        if (empty($type)) {
            return $mapping;
        } elseif (is_string($type) && array_key_exists($type, $mapping)) {
            return $mapping[$type];
        } elseif (is_array($type)) {
            return array_intersect_key($mapping, array_flip($type));
        }

        return [];
    }
}
