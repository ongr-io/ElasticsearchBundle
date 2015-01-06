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

use Doctrine\Common\Util\Inflector;
use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;

/**
 * Manager class.
 */
class Manager
{
    /**
     * @var Connection Elasticsearch connection.
     */
    private $connection;

    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @var array Repository structure info.
     */
    private $bundlesMapping = [];

    /**
     * @var array Document type map to repositories.
     */
    private $typesMapping = [];

    /**
     * @param Connection|null        $connection
     * @param MetadataCollector|null $metadataCollector
     * @param array                  $typesMapping
     * @param array                  $bundlesMapping
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
        $mapping = $this->getDocumentMapping($object);
        $document = $this->convertToArray($object, $mapping['aliases']);

        $this->getConnection()->bulk(
            'index',
            $mapping['type'],
            $document
        );
    }

    /**
     * Converts object to an array.
     *
     * @param DocumentInterface $object
     * @param array             $aliases
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    private function convertToArray($object, $aliases)
    {
        $array = [];

        // Special fields.
        if ($object instanceof DocumentInterface) {
            if ($object->getId()) {
                $array['_id'] = $object->getId();
            }

            if ($object->hasParent()) {
                $array['_parent'] = $object->getParent();
            }

            if ($object->getTtl()) {
                $array['_ttl'] = $object->getTtl();
            }
        }

        // Variable $name defined in client.
        foreach ($aliases as $name => $alias) {
            try {
                $method = 'get' . ucfirst(Inflector::classify($alias['propertyName']));
                if (method_exists($object, $method)) {
                    $value = $object->{$method}();
                } else {
                    $value = $object->{$alias['propertyName']};
                }
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "Cannot access {$alias['propertyName']} property. "
                    . 'Please define a setter or create document with Manager::createDocument.'
                );
            }

            if (isset($value)) {
                if (array_key_exists('aliases', $alias)) {
                    $new = null;
                    if ($alias['multiple']) {
                        $this->isTraversable($value);
                        foreach ($value as $item) {
                            $this->checkVariableType($item, [$alias['namespace'], $alias['proxyNamespace']]);
                            $new[] = $this->convertToArray($item, $alias['aliases']);
                        }
                    } else {
                        $this->checkVariableType($value, [$alias['namespace'], $alias['proxyNamespace']]);
                        $new = $this->convertToArray($value, $alias['aliases']);
                    }
                    $value = $new;
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format(\DateTime::ISO8601);
                }

                $array[$name] = $value;
            }
        }

        return $array;
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
            if (in_array(get_class($document), [$repository['namespace'], $repository['proxyNamespace']])) {
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
     * @param object $object
     * @param array  $expectedClasses
     *
     * @throws \InvalidArgumentException
     */
    private function checkVariableType($object, array $expectedClasses)
    {
        if (!is_object($object)) {
            $msg = 'Expected variable of type object, got ' . gettype($object) . ". (field isn't multiple)";
            throw new \InvalidArgumentException($msg);
        }

        $class = get_class($object);
        if (!in_array($class, $expectedClasses)) {
            throw new \InvalidArgumentException("Expected object of type {$expectedClasses[0]}, got {$class}.");
        }
    }

    /**
     * Check if object is traversable, throw exception otherwise.
     *
     * @param mixed $value
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    private function isTraversable($value)
    {
        if (!(is_array($value) || (is_object($value) && $value instanceof \Traversable))) {
            throw new \InvalidArgumentException("Variable isn't traversable, although field is set to multiple.");
        }

        return true;
    }
}
