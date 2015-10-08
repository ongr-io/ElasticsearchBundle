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

use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;

/**
 * This class hold aggregations from Elasticsearch result.
 */
class AggregationIterator implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * Constructor.
     *
     * @param array $rawData
     */
    public function __construct($rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * Returns the raw data which was passed from the iterator.
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->rawData[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        switch (true) {
            case isset($this->rawData[$offset]['buckets']):
                $result = new AggregationIterator($this->rawData[$offset]['buckets']);
                break;
            case isset($this->rawData[$offset]['hits']):
                $result = '';
                break;
            default:
                $result = new ValueAggregation($this->rawData[$offset]);
                break;
        }

        return $result;
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

        if ($this->offsetExists(AbstractAggregation::PREFIX.$currentPath)) {
            if (!strstr($path, '.')) {
                return $this->offsetGet(AbstractAggregation::PREFIX.$currentPath);
            } else {
                return $this->offsetGet(AbstractAggregation::PREFIX.$currentPath)
                    ->find(substr($path, strlen($currentPath) + 1));
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->rawData);
    }
}
