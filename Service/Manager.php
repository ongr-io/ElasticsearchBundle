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
     * After commit to make data available the refresh or flush operation is needed
     * so one of those methods has to be defined, the default is refresh.
     *
     * @var string
     */
    private $commitMode = 'refresh';

    /**
     * The size that defines after how much document inserts call commit function.
     *
     * @var int
     */
    private $bulkCommitSize = 100;

    /**
     * Container to count how many documents was passed to the bulk query.
     *
     * @var int
     */
    private $bulkCount = 0;

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
     * @return string
     */
    public function getCommitMode()
    {
        return $this->commitMode;
    }

    /**
     * @param string $commitMode
     */
    public function setCommitMode($commitMode)
    {
        if ($commitMode === 'refresh' || $commitMode === 'flush') {
            $this->commitMode = $commitMode;
        } else {
            throw new \LogicException('The commit method must be either refresh or flush.');
        }
    }

    /**
     * @return int
     */
    public function getBulkCommitSize()
    {
        return $this->bulkCommitSize;
    }

    /**
     * @param int $bulkCommitSize
     */
    public function setBulkCommitSize($bulkCommitSize)
    {
        $this->bulkCommitSize = $bulkCommitSize;
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
        return new Repository($this, $types);
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
     *
     * @return array
     */
    public function flush(array $params = [])
    {
        return $this->client->indices()->flush($params);
    }

    /**
     * Refreshes elasticsearch index.
     *
     * @param array $params
     *
     * @return array
     */
    public function refresh(array $params = [])
    {
        return $this->client->indices()->refresh($params);
    }

    /**
     * Inserts the current query container to the index, used for bulk queries execution.
     *
     * @param array $params Parameters that will be passed to the flush or refresh queries.
     *
     * @return null|array
     */
    public function commit(array $params = [])
    {
        $this->isReadOnly('Commit');

        if (!empty($this->bulkQueries)) {
            $bulkQueries = array_merge($this->bulkQueries, $this->bulkParams);
            $this->bulkQueries = [];

            $bulkResponse = $this->client->bulk($bulkQueries);

            switch ($this->getCommitMode()) {
                case 'flush':
                    $this->flush($params);
                    break;
                case 'refresh':
                default:
                    $this->refresh($params);
                    break;
            }

            return $bulkResponse;
        }

        return null;
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
            case 'update':
                $this->bulkQueries['body'][] = $query;
                break;
            case 'delete':
                // Body for delete operation is not needed to apply.
            default:
                // Do nothing.
                break;
        }

        // We are using counter because there is to difficult to resolve this from bulkQueries array.
        $this->bulkCount++;

        if ($this->bulkCommitSize === $this->bulkCount) {
            $this->commit();
            $this->bulkCount = 0;
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
     *
     * @return array
     */
    public function createIndex($noMapping = false)
    {
        $this->isReadOnly('Create index');

        if ($noMapping) {
            unset($this->indexSettings['body']['mappings']);
        }

        return $this->getClient()->indices()->create($this->indexSettings);
    }

    /**
     * Drops elasticsearch index.
     */
    public function dropIndex()
    {
        $this->isReadOnly('Drop index');

        return $this->getClient()->indices()->delete(['index' => $this->getIndexName()]);
    }

    /**
     * Tries to drop and create fresh elasticsearch index.
     *
     * @param bool $noMapping Determines if mapping should be included.
     *
     * @return array
     */
    public function dropAndCreateIndex($noMapping = false)
    {
        try {
            $this->dropIndex();
        } catch (\Exception $e) {
            // Do nothing, our target is to create new index.
        }

        return $this->createIndex($noMapping);
    }

    /**
     * Puts mapping into elasticsearch client.
     *
     * @param array $types           Specific types to put.
     * @param bool  $ignoreConflicts Ignore elasticsearch merge conflicts.
     */
    public function updateMapping(array $types = [], $ignoreConflicts = true)
    {
        $this->isReadOnly('Mapping update');
        $params['index'] = $this->getIndexName();

        if (empty($types)) {
            $map = $this->getConfig()['mappings'];
            foreach ($map as $bundle) {
                if (strpos($bundle, ':')) {
                    $types[] = $bundle;
                } else {
                    $bundleMappings = $this->getMetadataCollector()->getMappings([$bundle]);
                    foreach ($bundleMappings as $document) {
                        $types[] = $document['bundle'].':'.$document['class'];
                    }
                }
            }
        }

        foreach ($types as $type) {
            $mapping = $this->getMetadataCollector()->getClientMapping([$type]);

            if ($mapping === null) {
                throw new \LogicException(sprintf('Mapping for type "%s" was not found.', $type));
            }

            try {
                $type = $this->getMetadataCollector()->getDocumentType($type);
                $params['type'] = $type;
                $params['body'] = $mapping;
                $params['ignore_conflicts'] = $ignoreConflicts;
                $this->client->indices()->putMapping(array_filter($params));
            } catch (\Exception $e) {
                throw new \LogicException(
                    'Only the documents[] can be passed to the type update command. ' .
                    'Maybe you added only a bundle. Please check if a document is mapped in the manager.'
                );
            }
        }
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
