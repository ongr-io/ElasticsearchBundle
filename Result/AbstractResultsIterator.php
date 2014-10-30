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
 * AbstractResultsIterator class.
 */
abstract class AbstractResultsIterator implements \Countable, \Iterator, \ArrayAccess
{
    /**
     * Raw documents.
     *
     * @var array
     */
    protected $documents;

    /**
     * Documents casted to objects cache.
     *
     * @var array
     */
    protected $converted = [];

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return object
     */
    abstract protected function convertDocument($rawData);

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->offsetGet($this->key());
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->documents);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->documents);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->documents);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->documents);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!isset($this->converted[$offset])) {
            $this->converted[$offset] = $this->convertDocument($this->documents[$offset]);

            // Clear memory.
            $this->documents[$offset] = null;
        }

        return $this->converted[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->documents[$offset] = $value;

        // Also invalidate converted document.
        unset($this->converted[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->documents[$offset], $this->converted[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->documents);
    }
}
