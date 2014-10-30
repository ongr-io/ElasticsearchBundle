<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result\Aggregation;

use ONGR\ElasticsearchBundle\Result\Converter;

/**
 * HitsAggregationIterator class.
 */
class HitsAggregationIterator implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var array
     */
    private $raw;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param array     $raw
     * @param Converter $converter
     */
    public function __construct($raw, $converter)
    {
        $this->raw = $raw;
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->converter->convertToDocument(current($this->raw['hits']));
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->raw['hits']);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->raw['hits']);
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
        reset($this->raw['hits']);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->raw['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->raw['hits'][$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->converter->convertToDocument($this->raw['hits'][$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Action to set values not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Action to unset values not supported.');
    }
}
