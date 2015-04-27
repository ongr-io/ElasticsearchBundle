<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\ORM;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\DSL\Query\TermsQuery;
use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\DSL\Sort\Sort;
use ONGR\ElasticsearchBundle\DSL\Suggester\AbstractSuggester;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Result\DocumentScanIterator;
use ONGR\ElasticsearchBundle\Result\IndicesResult;
use ONGR\ElasticsearchBundle\Result\RawResultIterator;
use ONGR\ElasticsearchBundle\Result\RawResultScanIterator;
use ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionIterator;

/**
 * Repository class.
 */
class Repository
{
    const RESULTS_ARRAY = 'array';
    const RESULTS_OBJECT = 'object';
    const RESULTS_RAW = 'raw';
    const RESULTS_RAW_ITERATOR = 'raw_iterator';

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var array
     */
    private $namespaces = [];

    /**
     * @var array
     */
    private $types = [];

    /**
     * @var array
     */
    private $fieldsCache = [];

    /**
     * Constructor.
     *
     * @param Manager $manager
     * @param array   $repositories
     */
    public function __construct($manager, $repositories)
    {
        $this->manager = $manager;
        $this->namespaces = $repositories;
        $this->types = $this->getTypes();
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $types = [];
        $meta = $this->getManager()->getBundlesMapping($this->namespaces);

        foreach ($meta as $namespace => $metadata) {
            $types[] = $metadata->getType();
        }

        return $types;
    }

    /**
     * Returns a single document data by ID or null if document is not found.
     *
     * @param string $id         Document Id to find.
     * @param string $resultType Result type returned.
     *
     * @return DocumentInterface|null
     *
     * @throws \LogicException
     */
    public function find($id, $resultType = self::RESULTS_OBJECT)
    {
        if (count($this->types) !== 1) {
            throw new \LogicException('Only one type must be specified for the find() method');
        }

        $params = [
            'index' => $this->getManager()->getConnection()->getIndexName(),
            'type' => $this->types[0],
            'id' => $id,
        ];

        try {
            $result = $this->getManager()->getConnection()->getClient()->get($params);
        } catch (Missing404Exception $e) {
            return null;
        }

        if ($resultType === self::RESULTS_OBJECT) {
            return (new Converter(
                $this->getManager()->getTypesMapping(),
                $this->getManager()->getBundlesMapping()
            ))->convertToDocument($result);
        }

        return $this->parseResult($result, $resultType, '');
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param int|null   $limit      Example: 5.
     * @param int|null   $offset     Example: 30.
     * @param string     $resultType Result type returned.
     *
     * @return array|DocumentIterator The objects.
     */
    public function findBy(
        array $criteria,
        array $orderBy = [],
        $limit = null,
        $offset = null,
        $resultType = self::RESULTS_OBJECT
    ) {
        $search = $this->createSearch();

        if ($limit !== null) {
            $search->setSize($limit);
        }
        if ($offset !== null) {
            $search->setFrom($offset);
        }

        foreach ($criteria as $field => $value) {
            $search->addQuery(new TermsQuery($field, is_array($value) ? $value : [$value]), 'must');
        }

        foreach ($orderBy as $field => $direction) {
            $search->addSort(new Sort($field, strcasecmp($direction, 'asc') == 0 ? Sort::ORDER_ASC : Sort::ORDER_DESC));
        }

        return $this->execute($search, $resultType);
    }

    /**
     * Returns search instance.
     *
     * @return Search
     */
    public function createSearch()
    {
        return new Search();
    }

    /**
     * Executes given search.
     *
     * @param Search $search
     * @param string $resultsType
     *
     * @return DocumentIterator|DocumentScanIterator|RawResultIterator|array
     *
     * @throws \Exception
     */
    public function execute(Search $search, $resultsType = self::RESULTS_OBJECT)
    {
        $results = $this
            ->getManager()
            ->getConnection()
            ->search($this->types, $this->checkFields($search->toArray()), $search->getQueryParams());

        return $this->parseResult($results, $resultsType, $search->getScroll());
    }

    /**
     * Delete by query.
     *
     * @param Search $search
     *
     * @return array
     */
    public function deleteByQuery(Search $search)
    {
        $results = $this
            ->getManager()
            ->getConnection()
            ->deleteByQuery($this->types, $search->toArray());

        return new IndicesResult($results);
    }

    /**
     * Fetches next set of results.
     *
     * @param string $scrollId
     * @param string $scrollDuration
     * @param string $resultsType
     *
     * @return array|DocumentScanIterator
     *
     * @throws \Exception
     */
    public function scan(
        $scrollId,
        $scrollDuration = Search::SCROLL_DURATION,
        $resultsType = self::RESULTS_OBJECT
    ) {
        $results = $this->getManager()->getConnection()->scroll($scrollId, $scrollDuration);

        return $this->parseResult($results, $resultsType, $scrollDuration);
    }

    /**
     * Get suggestions using suggest api.
     *
     * @param AbstractSuggester[]|AbstractSuggester $suggesters
     *
     * @return SuggestionIterator
     */
    public function suggest($suggesters)
    {
        if (!is_array($suggesters)) {
            $suggesters = [$suggesters];
        }

        $body = [];
        /** @var AbstractSuggester $suggester */
        foreach ($suggesters as $suggester) {
            $body = array_merge($suggester->toArray(), $body);
        }
        $results = $this->getManager()->getConnection()->getClient()->suggest(['body' => $body]);
        unset($results['_shards']);

        return new SuggestionIterator($results);
    }

    /**
     * Removes a single document data by ID.
     *
     * @param string $id Document ID to remove.
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function remove($id)
    {
        if (count($this->types) == 1) {
            $params = [
                'index' => $this->getManager()->getConnection()->getIndexName(),
                'type' => $this->types[0],
                'id' => $id,
            ];

            $response = $this->getManager()->getConnection()->delete($params);

            return $response;
        } else {
            throw new \LogicException('Only one type must be specified for the find() method');
        }
    }

    /**
     * Checks if all required fields are added.
     *
     * @param array $searchArray
     * @param array $fields
     *
     * @return array
     */
    private function checkFields($searchArray, $fields = ['_parent', '_ttl'])
    {
        if (empty($fields)) {
            return $searchArray;
        }

        // Checks if cache is loaded.
        if (empty($this->fieldsCache)) {
            foreach ($this->getManager()->getBundlesMapping($this->namespaces) as $ns => $properties) {
                $this->fieldsCache = array_unique(
                    array_merge(
                        $this->fieldsCache,
                        array_keys($properties->getFields())
                    )
                );
            }
        }

        // Adds cached fields to fields array.
        foreach (array_intersect($this->fieldsCache, $fields) as $field) {
            $searchArray['fields'][] = $field;
        }

        // Removes duplicates and checks if its needed to add _source.
        if (!empty($searchArray['fields'])) {
            $searchArray['fields'] = array_unique($searchArray['fields']);
            if (array_diff($searchArray['fields'], $fields) === []) {
                $searchArray['fields'][] = '_source';
            }
        }

        return $searchArray;
    }

    /**
     * Parses raw result.
     *
     * @param array  $raw
     * @param string $resultsType
     * @param string $scrollDuration
     *
     * @return DocumentIterator|DocumentScanIterator|RawResultIterator|array
     *
     * @throws \Exception
     */
    private function parseResult($raw, $resultsType, $scrollDuration)
    {
        switch ($resultsType) {
            case self::RESULTS_OBJECT:
                if (isset($raw['_scroll_id'])) {
                    $iterator = new DocumentScanIterator(
                        $raw,
                        $this->getManager()->getTypesMapping(),
                        $this->getManager()->getBundlesMapping()
                    );
                    $iterator
                        ->setRepository($this)
                        ->setScrollDuration($scrollDuration)
                        ->setScrollId($raw['_scroll_id']);

                    return $iterator;
                }

                return new DocumentIterator(
                    $raw,
                    $this->getManager()->getTypesMapping(),
                    $this->getManager()->getBundlesMapping()
                );
            case self::RESULTS_ARRAY:
                return $this->convertToNormalizedArray($raw);
            case self::RESULTS_RAW:
                return $raw;
            case self::RESULTS_RAW_ITERATOR:
                if (isset($raw['_scroll_id'])) {
                    $iterator = new RawResultScanIterator($raw);
                    $iterator
                        ->setRepository($this)
                        ->setScrollDuration($scrollDuration)
                        ->setScrollId($raw['_scroll_id']);

                    return $iterator;
                }

                return new RawResultIterator($raw);
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
     * Creates new instance of document.
     *
     * @return DocumentInterface
     *
     * @throws \LogicException
     */
    public function createDocument()
    {
        if (count($this->namespaces) > 1) {
            throw new \LogicException(
                'Repository can not create new document when it is associated with multiple namespaces'
            );
        }

        $class = $this->getManager()->getBundlesMapping()[reset($this->namespaces)]->getProxyNamespace();

        return new $class();
    }

    /**
     * Returns elasticsearch manager used in this repository for getting/setting documents.
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
