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
 * This class hold aggregations from Elasticsearch result.
 */
class AggregationIterator implements \ArrayAccess, \Iterator
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @var array
     */
    private $aggregations;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param array $rawData
     * @param null  $converter
     */
    public function __construct($rawData, $converter = null)
    {
        $this->rawData = $rawData;
        $this->aggregations = [];
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->rawData[$offset]) || isset($this->aggregations[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        if (isset($this->aggregations[$offset])) {
            return $this->aggregations[$offset];
        }

        if (isset($this->rawData[$offset]['buckets'])) {
            $this->aggregations[$offset] = new AggregationIterator($this->rawData[$offset]['buckets']);
        } elseif (isset($this->rawData[$offset]['hits'])) {
            if (!$this->converter) {
                throw new \InvalidArgumentException(
                    'Too get tophit aggregation converter must be passed though constructor or choose raw results.'
                );
            }
            $this->aggregations[$offset] = new HitsAggregationIterator(
                $this->rawData[$offset]['hits'],
                $this->converter
            );
        } else {
            $this->aggregations[$offset] = new ValueAggregation($this->rawData[$offset]);
        }

        // Clear memory.
        $this->rawData[$offset] = null;

        return $this->aggregations[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Data of this iterator can not be changed after initialization.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Data of this iterator can not be changed after initialization.');
    }

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
        next($this->rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->rawData);
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
        reset($this->rawData);
    }

    /**
     * Returns aggregation bucket.
     *
     * @param string $path
     *
     * @return AggregationIterator
     */
    public function find($path)
    {
        $currentPath = strstr($path, '.', true);
        if ($currentPath === false) {
            $currentPath = $path;
        }

        if ($this->offsetExists($currentPath)) {
            if (!strstr($path, '.')) {
                return $this->offsetGet($currentPath);
            } else {
                return $this->offsetGet($currentPath)->find(substr($path, strlen($currentPath) + 1));
            }
        }

        return null;
    }
}
