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

use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchBundle\Result\AbstractResultsIterator;
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchDSL\Query\QueryStringQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;

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
     * @var string Fully qualified class name
     */
    private $className;

    /**
     * @var string Elasticsearch type name
     */
    private $type;

    /**
     * Constructor.
     *
     * @param Manager $manager
     * @param string  $className
     */
    public function __construct($manager, $className)
    {
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Class name must be a string.');
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot create repository for non-existing class "%s".', $className)
            );
        }

        $this->manager = $manager;
        $this->type = $this->resolveType($className);
    }

    /**
     * Returns elasticsearch manager used in the repository.
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns a single document data by ID or null if document is not found.
     *
     * @param string $id         Document Id to find.
     * @param string $resultType Result type returned.
     *
     * @return object|null
     *
     * @throws \LogicException
     */
    public function find($id, $resultType = self::RESULTS_OBJECT)
    {
        $params = [
            'index' => $this->getManager()->getIndexName(),
            'type' => $this->type,
            'id' => $id,
        ];

        try {
            $result = $this->getManager()->getClient()->get($params);
        } catch (Missing404Exception $e) {
            return null;
        }

        if ($resultType === self::RESULTS_OBJECT) {
            return $this->getManager()->getConverter()->convertToDocument($result, $this);
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
            $search->addQuery(
                new QueryStringQuery(is_array($value) ? implode(' OR ', $value) : $value, ['default_field' => $field])
            );
        }

        foreach ($orderBy as $field => $direction) {
            $search->addSort(new FieldSort($field, $direction));
        }

        return $this->execute($search, $resultType);
    }

    /**
     * Finds only one entity by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param string     $resultType Result type returned.
     *
     * @throws \Exception
     *
     * @return object|null The object.
     */
    public function findOneBy(array $criteria, array $orderBy = [], $resultType = self::RESULTS_OBJECT)
    {
        $result = $this->findBy($criteria, $orderBy, null, null, $resultType);

        switch ($resultType) {
            case self::RESULTS_OBJECT:
            case self::RESULTS_RAW_ITERATOR:
                return $result->first();
            case self::RESULTS_ARRAY:
                return array_shift($result);
            case self::RESULTS_RAW:
                return array_shift($result['hits']['hits']);
            default:
                throw new \Exception('Wrong results type selected');
        }
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
     * @return DocumentIterator|RawIterator|array
     *
     * @throws \Exception
     */
    public function execute(Search $search, $resultsType = self::RESULTS_OBJECT)
    {
        $results = $this
            ->getManager()
            ->search([$this->type], $search->toArray(), $search->getQueryParams());

        return $this->parseResult($results, $resultsType, $search->getScroll());
    }

    /**
     * Counts documents by given search.
     *
     * @param Search $search
     * @param array  $params
     * @param bool   $returnRaw If set true returns raw response gotten from client.
     *
     * @return int|array
     */
    public function count(Search $search, array $params = [], $returnRaw = false)
    {
        $body = array_merge(
            [
                'index' => $this->getManager()->getIndexName(),
                'type' => $this->type,
                'body' => $search->toArray(),
            ],
            $params
        );

        $results = $this
            ->getManager()
            ->getClient()->count($body);

        if ($returnRaw) {
            return $results;
        } else {
            return $results['count'];
        }
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
        $params = [
            'index' => $this->getManager()->getIndexName(),
            'type' => $this->type,
            'body' => $search->toArray(),
        ];

        return $this
            ->getManager()
            ->getClient()
            ->deleteByQuery($params);
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
        $resultsType = self::RESULTS_OBJECT
    ) {
        $results = $this->getManager()->getClient()->scroll(['scroll_id' => $scrollId, 'scroll' => $scrollDuration]);

        return $this->parseResult($results, $resultsType, $scrollDuration);
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
        $params = [
            'index' => $this->getManager()->getIndexName(),
            'type' => $this->type,
            'id' => $id,
        ];

        $response = $this->getManager()->getClient()->delete($params);

        return $response;
    }

    /**
     * Partial document update.
     *
     * @param string $id     Document id to update.
     * @param array  $fields Fields array to update.
     * @param string $script Groovy script to update fields.
     * @param array  $params Additional parameters to pass to the client.
     *
     * @return array
     */
    public function update($id, array $fields = [], $script = null, array $params = [])
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

    /**
     * Resolves elasticsearch type by class name.
     *
     * @param string $className
     *
     * @return array
     */
    private function resolveType($className)
    {
        return $this->getManager()->getMetadataCollector()->getDocumentType($className);
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
            case self::RESULTS_OBJECT:
                return new DocumentIterator($raw, $this, $scrollConfig);
            case self::RESULTS_ARRAY:
                return $this->convertToNormalizedArray($raw);
            case self::RESULTS_RAW:
                return $raw;
            case self::RESULTS_RAW_ITERATOR:
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
     * Returns fully qualified class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
