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
use Elasticsearch\Common\Exceptions\ClientErrorResponseException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\AbstractResultsIterator;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchBundle\Result\Result;
use ONGR\ElasticsearchDSL\Search;

/**
 * Manager class.
 */
class Manager
{
    /**
     * @var string Manager name
     */
    private $name;

    /**
     * @var array Manager configuration
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
     * @var array Container for bulk queries
     */
    private $bulkQueries = [];

    /**
     * @var array Holder for consistency, refresh and replication parameters
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
     * @var Repository[] Repository local cache
     */
    private $repositories;

    /**
     * @param string            $name              Manager name
     * @param array             $config            Manager configuration
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
     * Returns repository by document class.
     *
     * @param string $className FQCN or string in Bundle:Document format
     *
     * @return Repository
     */
    public function getRepository($className)
    {
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Document class must be a string.');
        }

        $namespace = $this->getMetadataCollector()->getClassName($className);

        if (isset($this->repositories[$namespace])) {
            return $this->repositories[$namespace];
        }

        $repository = $this->createRepository($namespace);
        $this->repositories[$namespace] = $repository;

        return $repository;
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
        if ($commitMode === 'refresh' || $commitMode === 'flush' || $commitMode === 'none') {
            $this->commitMode = $commitMode;
        } else {
            throw new \LogicException('The commit method must be either refresh, flush or none.');
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
     * @param string $className
     *
     * @return Repository
     */
    private function createRepository($className)
    {
        return new Repository($this, $className);
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
     * @param object $document
     */
    public function persist($document)
    {
        $documentArray = $this->converter->convertToArray($document);
        $type = $this->getMetadataCollector()->getDocumentType(get_class($document));

        $this->bulk('index', $type, $documentArray);
    }

    /**
     * Adds document for removal.
     *
     * @param object $document
     */
    public function remove($document)
    {
        $data = $this->converter->convertToArray($document, [], ['_id']);

        if (!isset($data['_id'])) {
            throw new \LogicException(
                'In order to use remove() method document class must have property with @Id annotation.'
            );
        }

        $type = $this->getMetadataCollector()->getDocumentType(get_class($document));

        $this->bulk('delete', $type, ['_id' => $data['_id']]);
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
        return $this->client->indices()->flush(array_merge(['index' => $this->getIndexName()], $params));
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
        return $this->client->indices()->refresh(array_merge(['index' => $this->getIndexName()], $params));
    }

    /**
     * Inserts the current query container to the index, used for bulk queries execution.
     *
     * @param array $params Parameters that will be passed to the flush or refresh queries.
     *
     * @return null|array
     *
     * @throws ClientErrorResponseException
     */
    public function commit(array $params = [])
    {
        if (!empty($this->bulkQueries)) {
            $bulkQueries = array_merge($this->bulkQueries, $this->bulkParams);

            $bulkResponse = $this->client->bulk($bulkQueries);
            $this->bulkQueries = [];
            $this->bulkCount = 0;

            switch ($this->getCommitMode()) {
                case 'flush':
                    $this->flush($params);
                    break;
                case 'refresh':
                    $this->refresh($params);
                    break;
            }

            if ($bulkResponse['errors']) {
                foreach ($bulkResponse['items'] as $item) {
                    if (isset(reset($item)['error'])) {
                        throw new ClientErrorResponseException(
                            sprintf(
                                'Error when commiting items to elasticsearch. Type: %s. Reason: %s',
                                reset($item)['error']['type'],
                                reset($item)['error']['reason']
                            )
                        );
                    }
                }
            }

            $response = [];

            foreach ($bulkResponse['items'] as $item) {
                $response[] = reset($item)['_id'];
            }

            return $response;
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
     *
     * @return null|array
     */
    public function bulk($operation, $type, array $query)
    {
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

        $response = null;
        if ($this->bulkCommitSize === $this->bulkCount) {
            $response = $this->commit();
        }

        return $response;
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
        $this->getClient()->indices()->clearCache(['index' => $this->getIndexName()]);
    }

    /**
     * Returns a single document by ID. Returns NULL if document was not found.
     *
     * @param string $className Document class name or Elasticsearch type name
     * @param string $id        Document ID to find
     *
     * @return object
     */
    public function find($className, $id)
    {
        $type = $this->resolveTypeName($className);

        $params = [
            'index' => $this->getIndexName(),
            'type' => $type,
            'id' => $id,
        ];

        try {
            $result = $this->getClient()->get($params);
        } catch (Missing404Exception $e) {
            return null;
        }

        return $this->getConverter()->convertToDocument($result, $this);
    }

    /**
     * Executes given search.
     *
     * @param array  $types
     * @param Search $search
     * @param string $resultsType
     *
     * @return DocumentIterator|RawIterator|array
     */
    public function execute($types, Search $search, $resultsType = Result::RESULTS_OBJECT)
    {
        foreach ($types as &$type) {
            $type = $this->resolveTypeName($type);
        }

        $results = $this->search($types, $search->toArray(), $search->getQueryParams());

        return $this->parseResult($results, $resultsType, $search->getScroll());
    }

    /**
     * Parses raw result.
     *
     * @param array  $raw
     * @param string $resultsType
     * @param string $scrollDuration
     *
     * @return DocumentIterator|RawIterator|array
     *
     * @throws \Exception
     */
    private function parseResult($raw, $resultsType, $scrollDuration = null)
    {
        $scrollConfig = [];
        if (isset($raw['_scroll_id'])) {
            $scrollConfig['_scroll_id'] = $raw['_scroll_id'];
            $scrollConfig['duration'] = $scrollDuration;
        }

        switch ($resultsType) {
            case Result::RESULTS_OBJECT:
                return new DocumentIterator($raw, $this, $scrollConfig);
            case Result::RESULTS_ARRAY:
                return $this->convertToNormalizedArray($raw);
            case Result::RESULTS_RAW:
                return $raw;
            case Result::RESULTS_RAW_ITERATOR:
                return new RawIterator($raw, $this, $scrollConfig);
            default:
                throw new \Exception('Wrong results type selected');
        }
    }

    /**
     * Normalizes response array.
     *
     * @param array $data
     *
     * @return array
     */
    private function convertToNormalizedArray($data)
    {
        if (array_key_exists('_source', $data)) {
            return $data['_source'];
        }

        $output = [];

        if (isset($data['hits']['hits'][0]['_source'])) {
            foreach ($data['hits']['hits'] as $item) {
                $output[] = $item['_source'];
            }
        } elseif (isset($data['hits']['hits'][0]['fields'])) {
            foreach ($data['hits']['hits'] as $item) {
                $output[] = array_map('reset', $item['fields']);
            }
        }

        return $output;
    }

    /**
     * Fetches next set of results.
     *
     * @param string $scrollId
     * @param string $scrollDuration
     * @param string $resultsType
     *
     * @return AbstractResultsIterator
     *
     * @throws \Exception
     */
    public function scroll(
        $scrollId,
        $scrollDuration = '5m',
        $resultsType = Result::RESULTS_OBJECT
    ) {
        $results = $this->getClient()->scroll(['scroll_id' => $scrollId, 'scroll' => $scrollDuration]);

        return $this->parseResult($results, $resultsType, $scrollDuration);
    }

    /**
     * Clears scroll.
     *
     * @param string $scrollId
     */
    public function clearScroll($scrollId)
    {
        $this->getClient()->clearScroll(['scroll_id' => $scrollId]);
    }

    /**
     * Resolves type name by class name.
     *
     * @param string $className
     *
     * @return string
     */
    private function resolveTypeName($className)
    {
        if (strpos($className, ':') !== false || strpos($className, '\\') !== false) {
            return $this->getMetadataCollector()->getDocumentType($className);
        }

        return $className;
    }
}
