<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;

/**
 * Class AbstractResultsIterator.
 */
abstract class AbstractResultsIterator
{
    /**
     * @var array Documents.
     */
    private $documents = [];

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var array
     */
    private $aggregations = [];

    /**
     * @var MetadataCollector
     */
    private $metaDataCollector;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Elasticsearch manager configuration.
     *
     * @var array
     */
    private $managerConfig = [];

    /**
     * Constructor.
     *
     * @param array             $rawData
     * @param array             $managerConfig
     * @param MetadataCollector $metaDataCollector
     * @param Converter         $converter
     */
    public function __construct($rawData, $managerConfig, MetadataCollector $metaDataCollector, Converter $converter)
    {
        $this->metaDataCollector = $metaDataCollector;
        $this->converter = $converter;
        $this->managerConfig = $managerConfig;

        if (isset($rawData['aggregations'])) {
            $this->aggregations = &$rawData['aggregations'];
        }

        if (isset($rawData['hits']['hits'])) {
            $this->documents = $rawData['hits']['hits'];
        }
        if (isset($rawData['hits']['total'])) {
            $this->count = $rawData['hits']['total'];
        }
    }

    /**
     * Returns total count of documents.
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * @return array
     */
    protected function getManagerConfig()
    {
        return $this->managerConfig;
    }

    /**
     * @return Converter
     */
    protected function getConverter()
    {
        return $this->converter;
    }

    /**
     * Gets document array from the container.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    protected function getDocument($key)
    {
        if (!$this->documentExists($key)) {
            return null;
        }

        return $this->convertDocument($this->documents[$key]);
    }

    /**
     * @return array
     */
    protected function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Checks whether document exists in the container.
     *
     * @param mixed $key
     *
     * @return bool
     */
    protected function documentExists($key)
    {
        return array_key_exists($key, $this->documents);
    }

    /**
     * @return int
     */
    protected function getKey()
    {
        return key($this->documents);
    }

    /**
     * Advances key.
     *
     * @return $this
     */
    protected function advanceKey()
    {
        next($this->documents);

        return $this;
    }

    /**
     * Resets key.
     *
     * @return $this
     */
    protected function resetKey()
    {
        reset($this->documents);

        return $this;
    }

    /**
     * Removes set documents.
     *
     * @return $this
     */
    protected function clean()
    {
        $this->documents = [];
        $this->resetKey();

        return $this;
    }

    /**
     * Rewind's the iteration and returns first result.
     *
     * @return mixed|null
     */
    public function first()
    {
        $this->resetKey();

        return $this->getDocument($this->getKey());
    }

    /**
     * Converts raw array to document object or array, depends on iterator type.
     *
     * @param array $document
     *
     * @return DocumentInterface|array
     */
    abstract protected function convertDocument(array $document);
}
