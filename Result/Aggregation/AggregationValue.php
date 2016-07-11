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

/**
 * This is the class for plain aggregation result with nested aggregations support.
 */
class AggregationValue implements \ArrayAccess, \IteratorAggregate
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
     * Returns aggregation value by name.
     *
     * @param string $name
     *
     * @return array
     */
    public function getValue($name = 'key')
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        return $this->rawData[$name];
    }

    /**
     * Returns the document count of the aggregation
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->getValue('doc_count');
    }

    /**
     * Returns array of bucket values.
     *
     * @return AggregationValue[]|null
     */
    public function getBuckets()
    {
        if (!isset($this->rawData['buckets'])) {
            return null;
        }

        $buckets = [];

        foreach ($this->rawData['buckets'] as $bucket) {
            $buckets[] = new self($bucket);
        }

        return $buckets;
    }

    /**
     * Returns sub-aggregation.
     *
     * @param string $name
     *
     * @return AggregationValue|null
     */
    public function getAggregation($name)
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        return new self($this->rawData[$name]);
    }

    /**
     * Applies path method to aggregations.
     *
     * @param string $path
     *
     * @return AggregationValue|null
     */
    public function find($path)
    {
        $name = explode('.', $path, 2);
        $aggregation = $this->getAggregation($name[0]);

        if ($aggregation === null || !isset($name[1])) {
            return $aggregation;
        }

        return $aggregation->find($name[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!isset($this->rawData[$offset])) {
            return null;
        }

        return $this->rawData[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Aggregation result can not be changed on runtime.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Aggregation result can not be changed on runtime.');
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $buckets = $this->getBuckets();

        if ($buckets === null) {
            throw new \LogicException('Can not iterate over aggregation without buckets!');
        }

        return new \ArrayIterator($this->getBuckets());
    }
}
