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

use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Document\Suggester\AbstractSuggester;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;

/**
 * Manager class.
 */
class Manager
{
    /**
     * Elasticsearch connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @var array Repository structure info
     */
    private $bundlesMapping = [];

    /**
     * @var array Document type map to repositories
     */
    private $typesMapping = [];

    /**
     * @param Connection|null           $connection
     * @param MetadataCollector|null    $metadataCollector
     * @param array                     $typesMapping
     * @param array                     $bundlesMapping
     */
    public function __construct($connection, $metadataCollector, $typesMapping, $bundlesMapping)
    {
        $this->connection = $connection;
        $this->metadataCollector = $metadataCollector;
        $this->typesMapping = $typesMapping;
        $this->bundlesMapping = $bundlesMapping;
    }

    /**
     * Returns Elasticsearch connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
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

        foreach ($type as $selectedType) {
            $this->checkRepositoryType($selectedType);
        }

        return $this->createRepository($type);
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
     * Adds document to next flush.
     *
     * @param DocumentInterface $object
     */
    public function persist(DocumentInterface $object)
    {
        $repository = $this->getDocumentMapping($object);
        $document = $this->convertToArray($object, $repository['getters']);

        $this->getConnection()->bulk(
            'index',
            $repository['type'],
            $document
        );
    }

    /**
     * Converts object to an array.
     *
     * @param DocumentInterface $object
     * @param array             $getters
     *
     * @return array
     */
    private function convertToArray($object, $getters)
    {
        $document = [];

        // Special fields.
        if ($object instanceof DocumentInterface) {
            if ($object->getId()) {
                $document['_id'] = $object->getId();
            }

            if ($object->hasParent()) {
                $document['_parent'] = $object->getParent();
            }

            if ($object->getTtl()) {
                $document['_ttl'] = $object->getTtl();
            }
        }

        foreach ($getters as $field => $getter) {
            if ($getter['exec']) {
                $value = $object->{$getter['name']}();
            } else {
                $value = $object->{$getter['name']};
            }

            if ($value && isset($getter['properties'])) {
                $newValue = null;

                if ($getter['multiple']) {
                    $this->isTraversable($value);
                    foreach ($value as $item) {
                        $this->checkVariableType($item, $getter['namespace']);
                        $arrayValue = $this->convertToArray($item, $getter['properties']);
                        $newValue[] = $arrayValue;
                    }
                } else {
                    $this->checkVariableType($value, $getter['namespace']);
                    $newValue = $this->convertToArray($value, $getter['properties']);
                }

                $value = $newValue;
            }

            if ($value instanceof AbstractSuggester) {
                $value = $value->toArray();
            }

            if ($value instanceof \DateTime) {
                $value = $value->format(\DateTime::ISO8601);
            }

            if ($value) {
                $document[$field] = $value;
            }
        }

        return $document;
    }

    /**
     * Commits bulk batch to elasticsearch index.
     */
    public function commit()
    {
        $this->getConnection()->commit();
    }

    /**
     * Flushes elasticsearch index.
     */
    public function flush()
    {
        $this->getConnection()->flush();
    }

    /**
     * Refreshes elasticsearch index.
     */
    public function refresh()
    {
        $this->getConnection()->refresh();
    }

    /**
     * @param object $document
     *
     * @return null
     */
    public function getDocumentMapping($document)
    {
        foreach ($this->bundlesMapping as $repository) {
            if ($repository['namespace'] == get_class($document)) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getBundlesMapping()
    {
        return $this->bundlesMapping;
    }

    /**
     * @return MetadataCollector
     */
    public function getMetadataCollector()
    {
        return $this->metadataCollector;
    }

    /**
     * @return array
     */
    public function getTypesMapping()
    {
        return $this->typesMapping;
    }

    /**
     * Checks if specified repository and type is defined, throws exception otherwise.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException
     */
    private function checkRepositoryType($type)
    {
        if (!array_key_exists($type, $this->bundlesMapping)) {
            $exceptionMessage = "Undefined repository {$type}, valid repositories are: " .
                join(', ', array_keys($this->bundlesMapping)) . '.';
            throw new \InvalidArgumentException($exceptionMessage);
        }
    }

    /**
     * Check if class matches the expected one.
     *
     * @param mixed  $object
     * @param string $expectedClass
     *
     * @throws \InvalidArgumentException
     */
    private function checkVariableType($object, $expectedClass)
    {
        if (!is_object($object)) {
            $msg = 'Expected variable of type object, got ' . gettype($object) . ". (field isn't multiple)";
            throw new \InvalidArgumentException($msg);
        }

        $class = get_class($object);
        if ($class != $expectedClass) {
            throw new \InvalidArgumentException("Expected object of type {$expectedClass}, got {$class}.");
        }
    }

    /**
     * Check if object is traversable, throw exception otherwise.
     *
     * @param mixed $value
     *
     * @throws \InvalidArgumentException
     */
    private function isTraversable($value)
    {
        if (!(is_array($value) || (is_object($value) && $value instanceof \Traversable))) {
            throw new \InvalidArgumentException("Variable isn't traversable, although field is set to multiple.");
        }
    }
}
