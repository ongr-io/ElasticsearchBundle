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

/**
 * Document repository class.
 */
class Repository
{
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
        $this->className = $className;
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
     * @param string $id      Document ID to find
     * @param string $routing Custom routing for the document
     *
     * @return object
     */
    public function find($id, $routing = null)
    {
        return $this->manager->find($this->type, $id, $routing);
    }

    /**
     * Returns documents by a set of ids
     *
     * @param array $ids
     *
     * @return DocumentIterator The objects.
     */
    public function findByIds(array $ids)
    {
        $args = [];
        $manager = $this->getManager();
        $args['body']['docs'] = [];
        $args['index'] = $manager->getIndexName();
        $args['type'] = $this->getType();

        foreach ($ids as $id) {
            $args['body']['docs'][] = [
                '_id' => $id
            ];
        }

        $mgetResponse = $manager->getClient()->mget($args);

        $return = [
            'hits' => [
                'hits' => [],
                'total' => 0,
            ]
        ];

        foreach ($mgetResponse['docs'] as $item) {
            if ($item['found']) {
                $return['hits']['hits'][] = $item;
            }
        }

        $return['hits']['total'] = count($return['hits']['hits']);

        return new DocumentIterator($return, $manager);
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param int|null   $limit      Example: 5.
     * @param int|null   $offset     Example: 30.
     *
     * @return array|DocumentIterator The objects.
     */
    public function findBy(
        array $criteria,
        array $orderBy = [],
        $limit = null,
        $offset = null
    ) {
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
     *
     * @return object|null The object.
     */
    public function findOneBy(array $criteria, array $orderBy = [])
    {
        return $this->findBy($criteria, $orderBy, 1, null)->current();
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
     * Parses scroll configuration from raw response.
     *
     * @param array  $raw
     * @param string $scrollDuration
     *
     * @return array
     */
    public function getScrollConfiguration($raw, $scrollDuration)
    {
        $scrollConfig = [];
        if (isset($raw['_scroll_id'])) {
            $scrollConfig['_scroll_id'] = $raw['_scroll_id'];
            $scrollConfig['duration'] = $scrollDuration;
        }

        return $scrollConfig;
    }

    /**
     * Returns DocumentIterator with composed Document objects from array response.
     *
     * @param Search $search
     *
     * @return DocumentIterator
     */
    public function findDocuments(Search $search)
    {
        $results = $this->executeSearch($search);

        return new DocumentIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }


    /**
     * Returns ArrayIterator with access to unmodified documents directly.
     *
     * @param Search $search
     *
     * @return ArrayIterator
     */
    public function findArray(Search $search)
    {
        $results = $this->executeSearch($search);

        return new ArrayIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    /**
     * Returns RawIterator with access to node with all returned values included.
     *
     * @param Search $search
     *
     * @return RawIterator
     */
    public function findRaw(Search $search)
    {
        $results = $this->executeSearch($search);

        return new RawIterator(
            $results,
            $this->getManager(),
            $this->getScrollConfiguration($results, $search->getScroll())
        );
    }

    /**
     * Executes search to the elasticsearch and returns raw response.
     *
     * @param Search $search
     *
     * @return array
     */
    private function executeSearch(Search $search)
    {
        return $this->getManager()->search([$this->getType()], $search->toArray(), $search->getUriParams());
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
     * Removes a single document data by ID.
     *
     * @param string $id      Document ID to remove
     * @param string $routing Custom routing for the document
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function remove($id, $routing = null)
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
     * Returns fully qualified class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
