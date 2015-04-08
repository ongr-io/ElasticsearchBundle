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
     * @var array Raw documents.
     */
    protected $documents = [];

    /**
     * @var array Documents casted to objects cache.
     */
    protected $converted = [];

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return object|array
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
            if (!isset($this->documents[$offset])) {
                return null;
            }

            $this->converted[$offset] = $this->convertDocument($this->documents[$offset]);

            // Clear memory.
            unset($this->documents[$offset]);
            if (isset($this->converted[$offset - 10])) {
                unset($this->converted[$offset - 10]);
            }
        }

        return $this->converted[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $offset = $this->getKey();
        }

        if (is_object($value)) {
            $this->converted[$offset] = $value;
            $this->documents[$offset] = null;
        } elseif (is_array($value)) {
            $this->documents[$offset] = $value;
            // Also invalidate converted document.
            unset($this->converted[$offset]);
        }
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

    /**
     * Rewind's the iteration and returns first result.
     *
     * @return mixed|null
     */
    public function first()
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Return an integer key to be used for a new element in array.
     *
     * @return int
     */
    private function getKey()
    {
        $currentIntKeys = array_filter(array_keys($this->documents), 'is_int');
        if (empty($currentIntKeys)) {
            $offset = 0;
        } else {
            $offset = max($currentIntKeys) + 1;
        }

        return $offset;
    }
}
