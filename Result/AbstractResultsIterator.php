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
    private $totalCount = 0;

    /**
     * Constructor.
     *
     * @param array $rawData
     */
    public function __construct($rawData)
    {
        if (isset($rawData['hits']['hits'])) {
            $this->setDocuments($rawData['hits']['hits']);
        }
        if (isset($rawData['hits']['total'])) {
            $this->setTotalCount($rawData['hits']['total']);
        }
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     *
     * @return $this
     */
    protected function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * @param array $documents
     *
     * @return $this
     */
    protected function setDocuments(&$documents)
    {
        $this->documents = &$documents;

        return $this;
    }

    /**
     * @param mixed $document
     * @param mixed $key
     *
     * @return $this
     */
    protected function addDocument($document, $key)
    {
        if ($key === null) {
            $this->documents[] = $document;
        } else {
            $this->documents[$key] = $document;
        }

        return $this;
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    protected function getDocument($key)
    {
        return isset($this->documents[$key]) ? $this->documents[$key] : null;
    }

    /**
     * Checks whether document exists.
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
     * Removes document.
     *
     * @param mixed $key
     *
     * @return $this
     */
    protected function removeDocument($key)
    {
        unset($this->documents[$key]);

        return $this;
    }

    /**
     * Removes document but leaves it existing.
     *
     * @param mixed $key
     *
     * @return $this
     */
    protected function clearDocument($key)
    {
        if (isset($this->documents[$key])) {
            $this->documents[$key] = null;
        }

        return $this;
    }

    /**
     * @return int
     */
    protected function getCount()
    {
        return count($this->documents);
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
}
