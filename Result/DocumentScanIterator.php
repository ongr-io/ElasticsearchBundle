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

use ONGR\ElasticsearchBundle\ORM\Repository;

/**
 * DocumentScanIterator class.
 */
class DocumentScanIterator extends DocumentIterator
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var string
     */
    private $scrollDuration;

    /**
     * @var string
     */
    private $scrollId;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @param Repository $repository
     *
     * @return DocumentScanIterator
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @param string $scrollDuration
     *
     * @return DocumentScanIterator
     */
    public function setScrollDuration($scrollDuration)
    {
        $this->scrollDuration = $scrollDuration;

        return $this;
    }

    /**
     * @param string $scrollId
     *
     * @return DocumentScanIterator
     */
    public function setScrollId($scrollId)
    {
        $this->scrollId = $scrollId;

        return $this;
    }

    /**
     * @return string
     */
    public function getScrollId()
    {
        return $this->scrollId;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getTotalCount();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if (array_key_exists($this->key, $this->documents)) {
            return true;
        }

        $raw = $this->repository->scan($this->scrollId, $this->scrollDuration, Repository::RESULTS_RAW);
        $this->setScrollId($raw['_scroll_id']);

        $this->documents = [];

        foreach($raw['hits']['hits'] as $key=>$value) {
            $this->documents[$key + $this->key] = $value;
        }

        return isset($this->documents[$this->key]);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->key++;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!isset($this->converted[$offset])) {
            if (!isset($this->documents[$offset])) {
                return null;
            }

            $this->converted[$offset] = $this->convertDocument($this->documents[$offset]);

            // Clear memory.
            if (isset($this->converted[$offset - 20])) {
                $this->converted[$offset - 20] = null;
            }

            if (isset($this->documents[$offset])) {
                $this->documents[$offset] = null;
            }
        }

        return $this->converted[$offset];
    }
}
