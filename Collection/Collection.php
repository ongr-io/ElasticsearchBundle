<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Collection;

/**
 * This class is a holder for collection of objects.
 */
class Collection implements \Countable, \Iterator, \ArrayAccess
{
    /**
     * @var array
     */
    private $elements = [];

    /**
     * Constructor.
     *
     * @param array $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->offsetExists($this->key());
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->elements[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->elements);
    }
}
