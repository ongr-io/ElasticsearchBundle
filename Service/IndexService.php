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
use ONGR\ElasticsearchBundle\Event\BulkEvent;
use ONGR\ElasticsearchBundle\Event\CommitEvent;
use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Exception\BulkWithErrorsException;
use ONGR\ElasticsearchBundle\Result\ArrayIterator;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Document repository class.
 */
class IndexService
{
    private $client;

    private $indexName;

    private $converter;

    private $eventDispatcher;

    private $stopwatch;

    private $bulkCommitSize = 100;

    private $bulkQueries = [];

    private $commitMode = 'refresh';

    /**
     * @deprecated will be removed in v7 since there will be no more types in the indexes.
     */
    private $typeName;

    public function __construct(Client $client, Converter $converter, EventDispatcherInterface $eventDispatcher, array $mapping = [])
    {
        $this->client = $client;
        $this->typeName = $mapping['type'];
        $this->converter = $converter;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @deprecated will be removed in v7 since there will be no more types in the indexes.
     */
    public function getTypeName(): string
    {
        return $this->typeName;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getConverter(): Converter
    {
        return $this->converter;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
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

    public function getCommitMode(): string
    {
        return $this->commitMode;
    }

    public function setCommitMode(string $commitMode): IndexService
    {
        $this->commitMode = $commitMode;
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

    public function createIndex($noMapping = false): array
    {
        return $this->getClient()->indices()->create($this->indexSettings);
    }

    public function dropIndex(): array
    {
        return $this->getClient()->indices()->delete(['index' => $this->getIndexName()]);
    }

    public function dropAndCreateIndex($noMapping = false)
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

    public function indexExists(): bool
    {
        return $this->getClient()->indices()->exists(['index' => $this->getIndexName()]);
    }

    /**
     * Returns a single document by provided ID or null if a document was not found.
     *
     * @param string $id      Document ID to find
     * @param array  $params  Custom parameters added to the query url
     *
     * @return object
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

        return $this->getConverter()->convertToDocument($result, $this);
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
     *
     * @return object|null The object.
     */
    public function findOneBy(array $criteria, array $orderBy = [])
    {
        return $this->findBy($criteria, $orderBy, 1, null)->current();
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
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    public function findArray(Search $search): ArrayIterator
    {
        $results = $this->executeSearch($search);

        return new ArrayIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    public function findRaw(Search $search): RawIterator
    {
        $results = $this->executeSearch($search);

        return new RawIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    private function executeSearch(Search $search): array
    {
        return $this->search([$this->getTypeName()], $search->toArray(), $search->getUriParams());
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
            $params = array_merge($requestParams, $params);
        }

//        $this->stopwatch('start', 'search');
        $result = $this->client->search($requestParams);
//        $this->stopwatch('stop', 'search');

        return $result;
    }

    public function bulk(string $operation, array $header, array $query = []): array
    {
        $this->eventDispatcher->dispatch(
            Events::BULK,
            new BulkEvent($operation, $this->getTypeName(), $header, $query)
        );

        $this->bulkQueries[] = $header;

        if (!empty($query)) $this->bulkQueries[] = $query;

        $response = [];
        // %2 is not very accurate, but better than use counter. This place is experimental for now.
        if ($this->getBulkCommitSize() >= count($this->bulkQueries % 2)) {
            $response = $this->commit();
        }

        return $response;
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

    public function commit(array $params = []): array
    {
        $bulkResponse = [];
        if (!empty($this->bulkQueries)) {
            $this->eventDispatcher->dispatch(
                Events::PRE_COMMIT,
                new CommitEvent($this->getCommitMode(), $this->bulkQueries)
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

            $this->stopwatch('start', 'refresh');

            switch ($this->getCommitMode()) {
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
                new CommitEvent($this->getCommitMode(), $this->bulkQueries, $bulkResponse)
            );

            $this->bulkQueries = [];

            $this->stopwatch('stop', $this->getCommitMode());
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
