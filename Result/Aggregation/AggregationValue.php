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
 * This class represent`s aggregation buckets in the objective way.
 */
class AggregationValue implements \ArrayAccess, \IteratorAggregate
{
    const BUCKETS_KEY = 'buckets';
    const DOC_COUNT_KEY = 'doc_count';

    /**
     * @var array
     */
    private $rawData;

    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * Returns aggregation value by the aggregation name.
     */
    public function getValue(string $name)
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        return $this->rawData[$name];
    }

    public function getCount(): int
    {
        return (int) $this->getValue(self::DOC_COUNT_KEY);
    }

    /**
     * Returns array of bucket values.
     *
     * @return AggregationValue[]
     */
    public function getBuckets(): array
    {
        if (!isset($this->rawData[self::BUCKETS_KEY])) {
            return [];
        }

        $buckets = [];

        foreach ($this->rawData[self::BUCKETS_KEY] as $bucket) {
            $buckets[] = new self($bucket);
        }

        return $buckets;
    }

    /**
     * Returns sub-aggregation.
     */
    public function getAggregation(string $name): ?self
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        return new self($this->rawData[$name]);
    }

    /**
     * Search'es the aggregation by defined path.
     */
    public function find(string $path): ?self
    {
        $name = explode('.', $path, 2);
        $aggregation = $this->getAggregation($name[0]);

        if ($aggregation === null || !isset($name[1])) {
            return $aggregation;
        }

        return $aggregation->find($name[1]);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->rawData);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->rawData[$offset])) {
            return null;
        }

        return $this->rawData[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Aggregation result can not be changed on runtime.');
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException('Aggregation result can not be changed on runtime.');
    }

    public function getIterator(): \ArrayIterator
    {
        $buckets = $this->getBuckets();

        if ($buckets === null) {
            throw new \LogicException('Can not iterate over aggregation without buckets!');
        }

        return new \ArrayIterator($this->getBuckets());
    }
}
