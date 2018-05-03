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

use ONGR\ElasticsearchBundle\Result\ArrayIterator;
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Event\BulkEvent;
use ONGR\ElasticsearchBundle\Event\CommitEvent;
use ONGR\ElasticsearchBundle\Exception\BulkWithErrorsException;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Document repository class.
 */
class Repository
{
    /**
     * @var string Fully qualified class namespace
     */
    private $classNamespace;

    /**
     * @var string Elasticsearch type name
     */
    private $type;

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @param string            $classNamespace
     * @param array             $config
     * @param Client            $client
     * @param array             $indexSettings
     * @param MetadataCollector $metadataCollector
     * @param Converter         $converter
     */
    public function __construct(
        $classNamespace,
        array $config,
        $client,
        array $indexSettings,
        $metadataCollector,
        $converter
    ) {
        if (!class_exists($classNamespace)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot create repository for non-existing class "%s".', $classNamespace)
            );
        }

        $this->classNamespace = $classNamespace;
        $this->config = $config;
        $this->client = $client;
        $this->indexSettings = $indexSettings;
        $this->metadataCollector = $metadataCollector;
        $this->converter = $converter;
        $this->type = $this->resolveType($classNamespace);
    }

    /**
     * @return array
     */
    public function getType()
    {
        return $this->type;
    }

    public function createSearch() :Search
    {
        return new Search();
    }

    /**
     * Returns a single document by ID. Returns NULL if document was not found.
     *
     * @param string $id        Document ID to find
     * @param string $routing   Custom routing for the document
     *
     * @return object
     */
    public function find($id, $routing = null)
    {
        $params = [
            'index' => $this->getIndexName(),
            'type' => $this->getType(),
            'id' => $id,
        ];

        if ($routing) {
            $params['routing'] = $routing;
        }

        $result = $this->getClient()->get($params);

        return $this->getConverter()->convertToDocument($result, $this);
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param int|null   $limit      Example: 5.
     * @param int|null   $offset     Example: 30.
     */
    public function findBy(
        array $criteria,
        array $orderBy = [],
        $limit = null,
        $offset = null
    ):DocumentIterator {
        $search = $this->createSearch();

        if ($limit !== null) {
            $search->setSize($limit);
        }
        if ($offset !== null) {
            $search->setFrom($offset);
        }

        foreach ($criteria as $field => $value) {
            if (preg_match('/^!(.+)$/', $field)) {
                $boolType = BoolQuery::MUST_NOT;
                $field = preg_replace('/^!/', '', $field);
            } else {
                $boolType = BoolQuery::MUST;
            }

            $search->addQuery(
                new QueryStringQuery(is_array($value) ? implode(' OR ', $value) : $value, ['default_field' => $field]),
                $boolType
            );
        }

        foreach ($orderBy as $field => $direction) {
            $search->addSort(new FieldSort($field, $direction));
        }

        return $this->findDocuments($search);
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     */
    public function findOneBy(array $criteria, array $orderBy = [])
    {
        return $this->findBy($criteria, $orderBy, 1, null)->current();
    }

    public function findDocuments(Search $search) :DocumentIterator
    {
        $results = $this->executeSearch($search);

        return new DocumentIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    public function findArray(Search $search):ArrayIterator
    {
        $results = $this->executeSearch($search);

        return new ArrayIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    public function findRaw(Search $search):RawIterator
    {
        $results = $this->executeSearch($search);

        return new RawIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    private function executeSearch(Search $search):array
    {
        return $this->getManager()->search([$this->getType()], $search->toArray(), $search->getUriParams());
    }

    public function getScrollConfiguration(array $raw, string $scrollDuration):array
    {
        $scrollConfig = [];
        if (isset($raw['_scroll_id'])) {
            $scrollConfig['_scroll_id'] = $raw['_scroll_id'];
            $scrollConfig['duration'] = $scrollDuration;
        }

        return $scrollConfig;
    }

    public function count(Search $search, array $params = []):int
    {
        $body = array_merge(
            [
                'index' => $this->getIndexName(),
                'type' => $this->type,
                'body' => $search->toArray(),
            ],
            $params
        );

        $results = $this
            ->getClient()->count($body);

            return $results['count'];
    }

    public function remove($id, $routing = null):array
    {
        $params = [
            'index' => $this->getManager()->getIndexName(),
            'type' => $this->type,
            'id' => $id,
        ];

        if ($routing) {
            $params['routing'] = $routing;
        }

        $response = $this->getManager()->getClient()->delete($params);

        return $response;
    }

    public function update(string $id, array $fields = [], string $script = null, array $params = []):array
    {
        $body = array_filter(
            [
                'doc' => $fields,
                'script' => $script,
            ]
        );

        $params = array_merge(
            [
                'id' => $id,
                'index' => $this->getManager()->getIndexName(),
                'type' => $this->type,
                'body' => $body,
            ],
            $params
        );

        return $this->getManager()->getClient()->update($params);
    }

    private function resolveType($className):string
    {
        return $this->getMetadataCollector()->getDocumentType($className);
    }

    public function getClassNamespace():string
    {
        return $this->classNamespace;
    }

    public function getClient():Client
    {
        return $this->client;
    }

    public function getConfig():array
    {
        return $this->config;
    }

    public function getMetadataCollector():MetadataCollector
    {
        return $this->metadataCollector;
    }

    public function getConverter():Converter
    {
        return $this->converter;
    }

    public function getCommitMode():string
    {
        return $this->commitMode;
    }

    public function setCommitMode(string $commitMode)
    {
        if ($commitMode === 'refresh' || $commitMode === 'flush' || $commitMode === 'none') {
            $this->commitMode = $commitMode;
        } else {
            throw new \LogicException('The commit method must be either refresh, flush or none.');
        }
    }

    public function getBulkCommitSize():int
    {
        return $this->bulkCommitSize;
    }

    public function setBulkCommitSize(int $bulkCommitSize)
    {
        $this->bulkCommitSize = $bulkCommitSize;
    }

    public function search(array $types, array $query, array $queryStringParams = []):array
    {
        $params = [];
        $params['index'] = $this->getIndexName();

        $resolvedTypes = [];
        foreach ($types as $type) {
            $resolvedTypes[] = $this->resolveTypeName($type);
        }

        if (!empty($resolvedTypes)) {
            $params['type'] = implode(',', $resolvedTypes);
        }

        $params['body'] = $query;

        if (!empty($queryStringParams)) {
            $params = array_merge($queryStringParams, $params);
        }

        $this->stopwatch('start', 'search');
        $result = $this->client->search($params);
        $this->stopwatch('stop', 'search');

        return $result;
    }

    public function msearch(array $body):array
    {
        $result = $this->client->msearch(
            [
                'index' => $this->getIndexName(), // set default index
                'body' => $body
            ]
        );
        return $result;
    }

    public function persist($document):array
    {
        $documentArray = $this->converter->convertToArray($document);
        $type = $this->getMetadataCollector()->getDocumentType(get_class($document));

        $this->bulk('index', $type, $documentArray);
    }

    public function flush(array $params = []):array
    {
        return $this->client->indices()->flush(array_merge(['index' => $this->getIndexName()], $params));
    }

    public function refresh(array $params = []):array
    {
        return $this->client->indices()->refresh(array_merge(['index' => $this->getIndexName()], $params));
    }

    public function commit(array $params = []):array
    {
        if (!empty($this->bulkQueries)) {
            $bulkQueries = array_merge($this->bulkQueries, $this->bulkParams);
            $bulkQueries['index']['_index'] = $this->getIndexName();
            $this->eventDispatcher->dispatch(
                Events::PRE_COMMIT,
                new CommitEvent($this->getCommitMode(), $bulkQueries)
            );

            $this->stopwatch('start', 'bulk');
            $bulkResponse = $this->client->bulk($bulkQueries);
            $this->stopwatch('stop', 'bulk');

            if ($bulkResponse['errors']) {
                throw new BulkWithErrorsException(
                    json_encode($bulkResponse),
                    0,
                    null,
                    $bulkResponse
                );
            }

            $this->bulkQueries = [];
            $this->bulkCount = 0;

            $this->stopwatch('start', 'refresh');

            switch ($this->getCommitMode()) {
                case 'flush':
                    $this->flush($params);
                    break;
                case 'refresh':
                    $this->refresh($params);
                    break;
            }

            $this->eventDispatcher->dispatch(
                Events::POST_COMMIT,
                new CommitEvent($this->getCommitMode(), $bulkResponse)
            );

            $this->stopwatch('stop', 'refresh');

            return $bulkResponse;
        }

        return [];
    }

    public function bulk(string $operation, string $type, array $query):array
    {
        if (!in_array($operation, ['index', 'create', 'update', 'delete'])) {
            throw new \InvalidArgumentException('Wrong bulk operation selected');
        }

        $this->eventDispatcher->dispatch(
            Events::BULK,
            new BulkEvent($operation, $type, $query)
        );

        $this->bulkQueries['body'][] = [
            $operation => array_filter(
                [
                    '_type' => $type,
                    '_id' => isset($query['_id']) ? $query['_id'] : null,
                    '_routing' => isset($query['_routing']) ? $query['_routing'] : null,
                    '_parent' => isset($query['_parent']) ? $query['_parent'] : null,
                ]
            ),
        ];
        unset($query['_id'], $query['_ttl'], $query['_parent'], $query['_routing']);

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

        $response = [];

        if ($this->bulkCommitSize === $this->bulkCount) {
            $response = $this->commit();
        }

        return $response;
    }

    public function setBulkParams(array $params)
    {
        $this->bulkParams = $params;
    }

    public function createIndex(bool $noMapping = false):array
    {
        if ($noMapping) {
            unset($this->indexSettings['body']['mappings']);
        }

        return $this->getClient()->indices()->create($this->indexSettings);
    }

    /**
     * Drops elasticsearch index.
     */
    public function dropIndex():array
    {
        return $this->getClient()->indices()->delete(['index' => $this->getIndexName()]);
    }

    /**
     * Tries to drop and create fresh elasticsearch index.
     */
    public function dropAndCreateIndex(bool $noMapping = false):array
    {
        try {
            if ($this->indexExists()) {
                $this->dropIndex();
            }
        } catch (\Exception $e) {
            // Do nothing, our target is to create new index.
        }

        return $this->createIndex($noMapping);
    }

    public function indexExists()
    {
        return $this->getClient()->indices()->exists(['index' => $this->getIndexName()]);
    }

    public function getIndexName():string
    {
        return $this->indexSettings['index'];
    }

    public function setIndexName(string $name)
    {
        $this->indexSettings['index'] = $name;
    }

    public function getIndexMappings():array
    {
        return $this->indexSettings['body']['mappings'];
    }

    /**
     * Returns Elasticsearch version number.
     */
    public function getVersionNumber():string
    {
        return $this->client->info()['version']['number'];
    }

    /**
     * Clears elasticsearch index cache.
     */
    public function clearIndexCache():array
    {
        return $this->getClient()->indices()->clearCache(['index' => $this->getIndexName()]);
    }

    /**
     * Fetches next scroll batch of results.
     */
    public function scroll(string $scrollId, string $scrollDuration = '5m'):array
    {
        $results = $this->getClient()->scroll(['scroll_id' => $scrollId, 'scroll' => $scrollDuration]);

        return $results;
    }

    /**
     * Clears scroll.
     */
    public function clearScroll(string $scrollId):array
    {
        return $this->getClient()->clearScroll(['scroll_id' => $scrollId]);
    }

    /**
     * Calls "Get Settings API" in Elasticsearch and will return you the currently configured settings.
     *
     * return array
     */
    public function getSettings():array
    {
        return $this->getClient()->indices()->getSettings(['index' => $this->getIndexName()]);
    }

    /**
     * Gets Elasticsearch aliases information.
     * @param $params
     *
     * @return array
     */
    public function getAliases($params = []):array
    {
        return $this->getClient()->indices()->getAliases(array_merge(['index' => $this->getIndexName()], $params));
    }

    /**
     * Resolves type name by class name.
     */
    private function resolveTypeName($classNamespace):string
    {
        return $this->getMetadataCollector()->getDocumentType($classNamespace);
    }

    /**
     * Starts and stops an event in the stopwatch
     *
     * @param string $action   only 'start' and 'stop'
     * @param string $name     name of the event
     */
    private function stopwatch($action, $name)
    {
        if (isset($this->stopwatch)) {
            $this->stopwatch->$action('ongr_es: '.$name, 'ongr_es');
        }
    }
}
