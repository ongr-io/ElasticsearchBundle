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
use ONGR\ElasticsearchBundle\Event\ElasticsearchEvent;
use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Mapping\ClassMetadata;
use ONGR\ElasticsearchBundle\Mapping\ClassMetadataCollection;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var ClassMetadataCollection
     */
    private $classMetadataCollection;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param Connection              $connection
     * @param ClassMetadataCollection $classMetadataCollection
     * @param EventDispatcher         $eventDispatcher
     */
    public function __construct($connection, $classMetadataCollection, $eventDispatcher)
    {
        $this->connection = $connection;
        $this->classMetadataCollection = $classMetadataCollection;
        $this->eventDispatcher = $eventDispatcher;
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

        foreach ($type as &$selectedType) {
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
     * @param DocumentInterface $document
     */
    public function persist(DocumentInterface $document)
    {
        $this->eventDispatcher->dispatch(Events::PRE_PERSIST, new ElasticsearchEvent($document));

        $mapping = $this->getDocumentMapping($document);
        $documentArray = $this->getConverter()->convertToArray($document);

        $this->getConnection()->bulk(
            'index',
            $mapping->getType(),
            $documentArray
        );

        $this->eventDispatcher->dispatch(Events::POST_PERSIST, new ElasticsearchEvent($document));
    }

    /**
     * Commits bulk batch to elasticsearch index.
     */
    public function commit()
    {
        $this->eventDispatcher->dispatch(Events::PRE_COMMIT, new Event());
        $this->getConnection()->commit();
        $this->eventDispatcher->dispatch(Events::POST_COMMIT, new Event());
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
     * Returns repository metadata for document.
     *
     * @param object $document
     *
     * @return ClassMetadata|null
     */
    public function getDocumentMapping($document)
    {
        foreach ($this->getBundlesMapping() as $repository) {
            if (in_array(get_class($document), [$repository->getNamespace(), $repository->getProxyNamespace()])) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Returns bundles mapping.
     *
     * @param array $repositories
     *
     * @return ClassMetadata[]
     */
    public function getBundlesMapping($repositories = [])
    {
        return $this->classMetadataCollection->getMetadata($repositories);
    }

    /**
     * @return array
     */
    public function getTypesMapping()
    {
        return $this->classMetadataCollection->getTypesMap();
    }

    /**
     * Checks if specified repository and type is defined, throws exception otherwise.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException
     */
    private function checkRepositoryType(&$type)
    {
        $mapping = $this->getBundlesMapping();

        if (array_key_exists($type, $mapping)) {
            return;
        }

        if (array_key_exists($type . 'Document', $mapping)) {
            $type .= 'Document';

            return;
        }

        $exceptionMessage = "Undefined repository `{$type}`, valid repositories are: `" .
            join('`, `', array_keys($this->getBundlesMapping())) . '`.';
        throw new \InvalidArgumentException($exceptionMessage);
    }

    /**
     * Returns converter instance.
     *
     * @return Converter
     */
    private function getConverter()
    {
        if (!$this->converter) {
            $this->converter = new Converter($this->getTypesMapping(), $this->getBundlesMapping());
        }

        return $this->converter;
    }
}
