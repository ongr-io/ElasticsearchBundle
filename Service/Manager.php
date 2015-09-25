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

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Manager class.
 */
class Manager
{
    /**
     * @var string Managers name.
     */
    private $name;

    /**
     * @var array Managers name.
     */
    private $config = [];

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * @var array Container for bulk queries.
     */
    private $bulkQueries = [];

    /**
     * @var array Holder for consistency, refresh and replication parameters.
     */
    private $bulkParams = [];

    /**
     * @var array
     */
    private $indexSettings;

    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @param string            $name              Managers name.
     * @param array             $config            Managers configuration.
     * @param Client            $client
     * @param array             $indexSettings
     * @param MetadataCollector $metadataCollector
     * @param Converter         $converter
     */
    public function __construct(
        $name,
        array $config,
        $client,
        array $indexSettings,
        $metadataCollector,
        $converter
    ) {
        $this->name = $name;
        $this->config = $config;
        $this->client = $client;
        $this->indexSettings = $indexSettings;
        $this->metadataCollector = $metadataCollector;
        $this->converter = $converter;

        $this->setReadOnly($config['readonly']);
    }

    /**
     * Returns Elasticsearch connection.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns repository with one or several active selected type.
     *
     * @param string|string[] $type
     *
     * @return Repository
     */
    public function getRepository($type)
    {
        $type = is_array($type) ? $type : [$type];

        return $this->createRepository($type);
    }

    /**
     * @return MetadataCollector
     */
    public function getMetadataCollector()
    {
        return $this->metadataCollector;
    }

    /**
     * @return Converter
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * Creates a repository.
     *
     * @param array $types
     *
     * @return Repository
     */
    private function createRepository(array $types)
    {
        return new Repository($this, $types, $this->converter);
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
     * Adds document to next flush.
     *
     * @param DocumentInterface $document
     */
    public function persist(DocumentInterface $document)
    {
        $documentArray = $this->converter->convertToArray($document);
        $typeMapping = $this->getMetadataCollector()->getDocumentMapping($document);

        $this->bulk('index', $typeMapping['type'], $documentArray);
    }

    /**
     * Flushes elasticsearch index.
     *
     * @param array $params
     */
    public function flush(array $params = [])
    {
        $this->client->indices()->flush($params);
    }

    /**
     * Refreshes elasticsearch index.
     *
     * @param array $params
     */
    public function refresh(array $params = [])
    {
        $this->client->indices()->refresh($params);
    }

    /**
     * Flushes the current query container to the index, used for bulk queries execution.
     */
    public function commit()
    {
        $this->isReadOnly('Commit');
        $this->bulkQueries = array_merge($this->bulkQueries, $this->bulkParams);
        $this->client->bulk($this->bulkQueries);
        $this->flush();
        $this->bulkQueries = [];
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
     * Creates fresh elasticsearch index.
     *
     * @param bool $noMapping Determines if mapping should be included.
     */
    public function createIndex($noMapping = false)
    {
        $this->isReadOnly('Create index');

        if ($noMapping) {
            unset($this->indexSettings['body']['mappings']);
        }
        $this->getClient()->indices()->create($this->indexSettings);
    }

    /**
     * Drops elasticsearch index.
     */
    public function dropIndex()
    {
        $this->isReadOnly('Drop index');

        $this->getClient()->indices()->delete(['index' => $this->getIndexName()]);
    }

    /**
     * Tries to drop and create fresh elasticsearch index.
     *
     * @param bool $noMapping Determines if mapping should be included.
     */
    public function dropAndCreateIndex($noMapping = false)
    {
        try {
            $this->dropIndex();
        } catch (\Exception $e) {
            // Do nothing, our target is to create new index.
        }

        $this->createIndex($noMapping);
    }

    /**
     * Puts mapping into elasticsearch client.
     *
     * @param array $types Specific types to put.
     *
     * @return array
     */
    public function updateMapping(array $types = [])
    {
        $this->isReadOnly('Mapping update');

        $params['index'] = $this->getIndexName();
        $params['types'] = array_keys($types);
        $params['body'] = $types;

        return $this->client->indices()->putMapping($params);
    }

    /**
     * Checks if connection index is already created.
     *
     * @return bool
     */
    public function indexExists()
    {
        return $this->getClient()->indices()->exists(['index' => $this->getIndexName()]);
    }

    /**
     * Returns index name this connection is attached to.
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexSettings['index'];
    }

    /**
     * Sets index name for this connection.
     *
     * @param string $name
     */
    public function setIndexName($name)
    {
        $this->indexSettings['index'] = $name;
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
     * Clears elasticsearch client cache.
     */
    public function clearCache()
    {
        $this->isReadOnly('Clear cache');

        $this->getClient()->indices()->clearCache(['index' => $this->getIndexName()]);
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
            throw new Forbidden403Exception("Manager is readonly! {$message} operation is not permitted.");
        }
    }
}
