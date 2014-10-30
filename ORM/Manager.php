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
     * @param Connection        $connection
     * @param MetadataCollector $metadataCollector
     * @param array             $typesMapping
     * @param array             $bundlesMapping
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
        return $this->createRepository(is_array($type) ? $type : [$type]);
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
            'create',
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
                $newValue = [];

                if (is_array($value) || $value instanceof \Traversable) {
                    foreach ($value as $item) {
                        $arrayValue = $this->convertToArray($item, $getter['properties']);
                        $newValue[] = $arrayValue;
                    }
                } else {
                    $newValue[] = $this->convertToArray($value, $getter['properties']);
                }

                $value = $newValue;
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
}
