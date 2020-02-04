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

use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Service\IndexService;

abstract class AbstractResultsIterator implements \Countable, \Iterator
{
    private $count = 0;
    private $raw;
    private $scrollId;
    private $scrollDuration;

    protected $documents = [];
    private $aggregations = [];

    private $converter;
    private $index;

    //Used to count scroll iteration.
    private $key = 0;

    public function __construct(
        array $rawData,
        IndexService $index,
        Converter $converter = null,
        array $scroll = []
    ) {
        $this->raw = $rawData;
        $this->converter = $converter;
        $this->index = $index;

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
        if (isset($rawData['hits']['total']['value'])) {
            $this->count = $rawData['hits']['total']['value'];
        }
    }

    public function __destruct()
    {
        // Clear scroll if initialized
        if ($this->isScrollable()) {
            $this->index->clearScroll($this->scrollId);
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
     */
    public function current()
    {
        return $this->getDocument($this->key());
    }

    /**
     * Move forward to the next element.
     */
    public function next(): self
    {
        return $this->advanceKey();
    }

    /**
     * Return the key of the current element.
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * Checks if current position is valid.
     */
    public function valid(): bool
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
    public function rewind(): void
    {
        $this->key = 0;
    }

    public function isScrollable(): bool
    {
        return !empty($this->scrollId);
    }

    protected function getConverter(): Converter
    {
        return $this->converter;
    }

    public function getIndex(): IndexService
    {
        return $this->index;
    }

    /**
     * Gets document array from the container.
     */
    protected function getDocument(int $key)
    {
        if (!$this->documentExists($key)) {
            return null;
        }

        return $this->convertDocument($this->documents[$key]);
    }

    /**
     * Checks if a document exists in the local cache container.
     */
    protected function documentExists(int $key): bool
    {
        return array_key_exists($key, $this->documents);
    }

    /**
     * Advances key.
     *
     * @return $this
     */
    protected function advanceKey(): self
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
     */
    public function first()
    {
        $this->rewind();

        return $this->getDocument($this->key());
    }

    protected function page(): self
    {
        if ($this->key() == $this->count() || !$this->isScrollable()) {
            return $this;
        }

        $raw = $this->index->getClient()->scroll(
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
     */
    public function getDocumentScore(): int
    {
        if (!$this->valid()) {
            throw new \LogicException('Document score is available only while iterating over results.');
        }

        if (!isset($this->documents[$this->key]['_score'])) {
            return null;
        }

        return (int) $this->documents[$this->key]['_score'];
    }

    /**
    * Returns sort of current hit.
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
     * Convert`s raw array to a document object or a normalized array, depends on the iterator type.
     */
    abstract protected function convertDocument(array $raw);
}
