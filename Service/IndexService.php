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
use Elasticsearch\ClientBuilder;
use ONGR\ElasticsearchBundle\Event\BulkEvent;
use ONGR\ElasticsearchBundle\Event\CommitEvent;
use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Event\PostCreateClientEvent;
use ONGR\ElasticsearchBundle\Exception\BulkWithErrorsException;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\IndexSettings;
use ONGR\ElasticsearchBundle\Result\ArrayIterator;
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IndexService
{
    private $client;
    private $namespace;
    private $converter;
    private $eventDispatcher;

    private $stopwatch;
    private $bulkCommitSize = 100;
    private $bulkQueries = [];
    private $indexSettings = [];
    private $tracer;

    public function __construct(
        string $namespace,
        Converter $converter,
        EventDispatcherInterface $eventDispatcher,
        IndexSettings $indexSettings,
        $tracer = null
    ) {
        $this->namespace = $namespace;
        $this->converter = $converter;
        $this->eventDispatcher = $eventDispatcher;
        $this->indexSettings = $indexSettings;
        $this->tracer = $tracer;
        $this->getClient();
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @deprecated will be removed in v7 since there will be no more types in the indexes.
     */
    public function getTypeName(): string
    {
        return $this->indexSettings->getType();
    }

    public function getIndexSettings()
    {
        return $this->indexSettings;
    }

    public function setIndexSettings($indexSettings): self
    {
        $this->indexSettings = $indexSettings;
        return $this;
    }

    public function getClient(): Client
    {
        if (!$this->client) {
            $client = ClientBuilder::create();
            $client->setHosts($this->indexSettings->getHosts());
            $this->tracer && $client->setTracer($this->tracer);
//            $client->setLogger()

            $this->eventDispatcher->dispatch(
                Events::POST_CLIENT_CREATE,
                new PostCreateClientEvent($this->namespace, $client)
            );
            $this->client = $client->build();
        }
        return $this->client;
    }

    public function getIndexName(): string
    {
        return $this->indexSettings->getIndexName();
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getConverter(): Converter
    {
        return $this->converter;
    }

    public function getBulkCommitSize(): int
    {
        return $this->bulkCommitSize;
    }

    public function setBulkCommitSize(int $bulkCommitSize)
    {
        $this->bulkCommitSize = $bulkCommitSize;
        return $this;
    }

    public function getStopwatch()
    {
        return $this->stopwatch;
    }

    public function setStopwatch($stopwatch)
    {
        $this->stopwatch = $stopwatch;
        return $this;
    }

    public function createIndex($noMapping = false, $params = []): array
    {
        $params = array_merge([
            'index' => $this->getIndexName(),
            'body' => $noMapping ? [] : $this->indexSettings->getIndexMetadata(),
        ], $params);

        #TODO Add event here.

        return $this->getClient()->indices()->create(array_filter($params));
    }

    public function dropIndex(): array
    {
        $indexName = $this->getIndexName();

        if ($this->getClient()->indices()->existsAlias(['name' => $this->getIndexName()])) {
            $aliases = $this->getClient()->indices()->getAlias(['name' => $this->getIndexName()]);
            $indexName = array_keys($aliases);
        }

        return $this->getClient()->indices()->delete(['index' => $indexName]);
    }

    public function dropAndCreateIndex($noMapping = false, $params = []): array
    {
        try {
            if ($this->indexExists()) {
                $this->dropIndex();
            }
        } catch (\Exception $e) {
            // Do nothing, our target is to create the new index.
        }

        return $this->createIndex($noMapping, $params);
    }

    public function indexExists(): bool
    {
        return $this->getClient()->indices()->exists(['index' => $this->getIndexName()]);
    }

    public function clearCache(): array
    {
        return $this->getClient()->indices()->clearCache(['index' => $this->getIndexName()]);
    }

    /**
     * Returns a single document by provided ID or null if a document was not found.
     */
    public function find($id, $params = [])
    {
        $requestParams = [
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
            'id' => $id,
        ];

        $requestParams = array_merge($requestParams, $params);

        $result = $this->getClient()->get($requestParams);

        if (!$result['found']) {
            return null;
        }

        $result['_source']['_id'] = $result['_id'];

        return $this->converter->convertArrayToDocument($this->namespace, $result['_source']);
    }

    public function findByIds(array $ids): DocumentIterator
    {
        $search = $this->createSearch();
        $search->addQuery(new IdsQuery($ids));

        return $this->findDocuments($search);
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param int|null   $limit      Default is 10.
     * @param int|null   $offset     Default is 0.
     *
     * @return array|DocumentIterator The objects.
     */
    public function findBy(
        array $criteria,
        array $orderBy = [],
        int $limit = 10,
        int $offset = 0
    ) {
        $search = $this->createSearch();
        $search->setSize($limit);
        $search->setFrom($offset);

        foreach ($criteria as $field => $value) {
            $search->addQuery(new TermQuery($field, $value));
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
     *
     * @return object|null The object.
     */
    public function findOneBy(array $criteria, array $orderBy = [])
    {
        return $this->findBy($criteria, $orderBy, 1)->current();
    }

    public function createSearch(): Search
    {
        return new Search();
    }

    public function getScrollConfiguration($raw, $scrollDuration): array
    {
        $scrollConfig = [];
        if (isset($raw['_scroll_id'])) {
            $scrollConfig['_scroll_id'] = $raw['_scroll_id'];
            $scrollConfig['duration'] = $scrollDuration;
        }

        return $scrollConfig;
    }

    public function findDocuments(Search $search): DocumentIterator
    {
        $results = $this->executeSearch($search);

        return new DocumentIterator(
            $results,
            $this,
            $this->converter,
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    public function findArray(Search $search): ArrayIterator
    {
        $results = $this->executeSearch($search);

        return new ArrayIterator(
            $results,
            $this,
            $this->converter,
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    public function findRaw(Search $search): RawIterator
    {
        $results = $this->executeSearch($search);

        return new RawIterator(
            $results,
            $this,
            $this->converter,
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    private function executeSearch(Search $search): array
    {
        return $this->search($search->toArray(), $search->getUriParams());
    }

    public function getIndexDocumentCount(): int
    {
        $body = [
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
            'body' => [],
        ];

        $results = $this->getClient()->count($body);

        return $results['count'];
    }

    public function remove($id, $routing = null)
    {
        $params = [
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
            'id' => $id,
        ];

        if ($routing) {
            $params['routing'] = $routing;
        }

        $response = $this->getClient()->delete($params);

        return $response;
    }

    public function update($id, array $fields = [], $script = null, array $params = []): array
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
                'index' => $this->getIndexName(),
                'type' => $this->getTypeName(),
                'body' => $body,
            ],
            $params
        );

        return $this->getClient()->update($params);
    }

    public function search(array $query, array $params = []): array
    {
        $requestParams = [
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
            'body' => $query,
        ];


        if (!empty($params)) {
            $requestParams = array_merge($requestParams, $params);
        }

//        $this->stopwatch('start', 'search');
        $result = $this->getClient()->search($requestParams);
//        $this->stopwatch('stop', 'search');

        return $result;
    }

    /**
     * Usage example
     *
     * $im->bulk('index', ['_id' => 1, 'title' => 'foo']);
     * $im->bulk('delete', ['_id' => 2]);
     * $im->bulk('create', ['title' => 'foo']);
     */
    public function bulk(string $operation, array $data = [], $autoCommit = true): array
    {
        $bulkParams = [
            '_type' => $this->getTypeName(),
            '_id' => $data['_id'] ?? null,
        ];

        unset($data['_index'], $data['_type'], $data['_id']);

        $this->eventDispatcher->dispatch(
            Events::BULK,
            new BulkEvent($operation, $bulkParams, $data)
        );

        $this->bulkQueries[] = [ $operation => $bulkParams];

        if (!empty($data)) {
            $this->bulkQueries[] = $data;
        }

        $response = [];

        // %X is not very accurate, but might be better than use counter. This place is experimental for now.
        if ($autoCommit && $this->getBulkCommitSize() <= count($this->bulkQueries) % $this->getBulkCommitSize() / 2) {
            $response = $this->commit();
        }

        return $response;
    }

    /**
     * Adds document to next flush.
     *
     * @param object $document
     */
    public function persist($document): void
    {
        $documentArray = array_filter($this->converter->convertDocumentToArray($document), function ($val) {
            // remove unset properties but keep other falsy values
            return !($val === null);
        });

        $this->bulk('index', $documentArray);
    }

    public function commit($commitMode = 'flush', array $params = []): array
    {
        $bulkResponse = [];
        if (!empty($this->bulkQueries)) {
            $this->eventDispatcher->dispatch(
                Events::PRE_COMMIT,
                new CommitEvent($commitMode, $this->bulkQueries, [])
            );

//            $this->stopwatch('start', 'bulk');
            $bulkResponse = $this->client->bulk(
                array_merge(
                    [
                    'index' => $this->getIndexName(),
                    'body' => $this->bulkQueries,
                    ],
                    $params
                )
            );
//            $this->stopwatch('stop', 'bulk');

            if ($bulkResponse['errors']) {
                throw new BulkWithErrorsException(
                    json_encode($bulkResponse),
                    0,
                    null,
                    $bulkResponse
                );
            }

//            $this->stopwatch('start', 'refresh');
            switch ($commitMode) {
                case 'flush':
                    $this->getClient()->indices()->flush();
                    break;
                case 'flush_synced':
                    $this->getClient()->indices()->flushSynced();
                    break;
                case 'refresh':
                    $this->getClient()->indices()->refresh();
                    break;
            }

            $this->eventDispatcher->dispatch(
                Events::POST_COMMIT,
                new CommitEvent($commitMode, $this->bulkQueries, $bulkResponse)
            );

            $this->bulkQueries = [];

//            $this->stopwatch('stop', $this->getCommitMode());
        }

        return $bulkResponse;
    }

    public function flush(array $params = []): array
    {
        return $this->client->indices()->flush(array_merge(['index' => $this->getIndexName()], $params));
    }

    public function refresh(array $params = []): array
    {
        return $this->client->indices()->refresh(array_merge(['index' => $this->getIndexName()], $params));
    }

    public function scroll($scrollId, $scrollDuration = '5m'): array
    {
        $results = $this->getClient()->scroll(['scroll_id' => $scrollId, 'scroll' => $scrollDuration]);

        return $results;
    }

    public function clearScroll($scrollId): array
    {
        return $this->getClient()->clearScroll(['scroll_id' => $scrollId]);
    }

    public function resetClient(): void
    {
        $this->client = null;
    }

    public function clearElasticIndexCache(): array
    {
        return $this->getClient()->indices()->clearCache(['index' => $this->getIndexName()]);
    }

    private function stopwatch($action, $name): void
    {
        if ($this->stopwatch && ($action == 'start' || $action == 'stop')) {
            $this->stopwatch->$action('ongr_es: '.$name, 'ongr_es');
        }
    }
}
