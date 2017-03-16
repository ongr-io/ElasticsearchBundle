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

use ONGR\ElasticsearchBundle\Service\Manager;

/**
 * Class AbstractResultsIterator.
 */
abstract class AbstractResultsIterator implements \Countable, \Iterator
{
    /**
     * Raw data returned from elasticsearch.
     *
     * @var array
     */
    private $raw;

    /**
     * @var array Documents.
     */
    protected $documents = [];

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var array
     */
    private $aggregations = [];

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * Elasticsearch manager configuration.
     *
     * @var array
     */
    private $managerConfig = [];

    /**
     * @var string If value is not null then results are scrollable.
     */
    private $scrollId;

    /**
     * @var string Scroll duration.
     */
    private $scrollDuration;

    /**
     * Used to count iteration.
     *
     * @var int
     */
    private $key = 0;

    /**
     * @param array   $rawData
     * @param Manager $manager
     * @param array   $scroll
     */
    public function __construct(
        array $rawData,
        Manager $manager,
        array $scroll = []
    ) {
        $this->raw = $rawData;
        $this->manager = $manager;
        $this->converter = $manager->getConverter();
        $this->managerConfig = $manager->getConfig();

        if (isset($scroll['_scroll_id']) && isset($scroll['duration'])) {
            $this->scrollId = $scroll['_scroll_id'];
            $this->scrollDuration = $scroll['duration'];
        }

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
     * Destructor.
     */
    public function __destruct()
    {
        // Clear scroll if initialized
        if ($this->isScrollable()) {
            $this->manager->clearScroll($this->scrollId);
        }
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @return array
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Returns specific aggregation by name.
     *
     * @param string $name
     *
     * @return array
     */
    public function getAggregation($name)
    {
        if (isset($this->aggregations[$name])) {
            return $this->aggregations[$name];
        }
        return null;
    }

    /**
     * @return Manager
     */
    protected function getManager()
    {
        return $this->manager;
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
     * Return the current element.
     *
     * @return mixed
     */
    public function current()
    {
        return $this->getDocument($this->key());
    }

    /**
     * Move forward to next element.
     */
    public function next()
    {
        $this->advanceKey();
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        if (!isset($this->documents)) {
            return false;
        }

        $valid = $this->documentExists($this->key());
        if ($valid) {
            return true;
        }

        $this->page();

        return $this->documentExists($this->key());
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * @return bool
     */
    public function isScrollable()
    {
        return !empty($this->scrollId);
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
     * Advances key.
     *
     * @return $this
     */
    protected function advanceKey()
    {
        if ($this->isScrollable() && ($this->documents[$this->key()] == end($this->documents))) {
            $this->page();
        } else {
            $this->key++;
        }

        return $this;
    }

    /**
     * Rewind's the iteration and returns first result.
     *
     * @return mixed|null
     */
    public function first()
    {
        $this->rewind();

        return $this->getDocument($this->key());
    }

    /**
     * Advances scan page.
     *
     * @return $this
     */
    protected function page()
    {
        if ($this->key() == $this->count() || !$this->isScrollable()) {
            return $this;
        }

//        $raw = $this->manager->scroll($this->scrollId, $this->scrollDuration, Result::RESULTS_RAW);
        $raw = $this->manager->getClient()->scroll(
            [
                'scroll' => $this->scrollDuration,
                'scroll_id' => $this->scrollId,
            ]
        );
        $this->rewind();
        $this->scrollId = $raw['_scroll_id'];
        $this->documents = $raw['hits']['hits'];

        return $this;
    }

    /**
     * Returns score of current hit.
     *
     * @return int
     */
    public function getDocumentScore()
    {
        if (!$this->valid()) {
            throw new \LogicException('Document score is available only while iterating over results.');
        }

        if (!isset($this->documents[$this->key]['_score'])) {
            return null;
        }

        return $this->documents[$this->key]['_score'];
    }

    /**
    * Returns sort of current hit.
    *
    * @return mixed
    */
    public function getDocumentSort()
    {
        if (!$this->valid()) {
            throw new \LogicException('Document sort is available only while iterating over results.');
        }

        if (!isset($this->documents[$this->key]['sort'])) {
            return null;
        }

        return $this->documents[$this->key]['sort'][0];
    }

    /**
     * Converts raw array to document object or array, depends on iterator type.
     *
     * @param array $document
     *
     * @return object|array
     */
    abstract protected function convertDocument(array $document);
}
