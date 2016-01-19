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
 * This is the class for plain aggregation result with nested aggregations support.
 */
class ValueAggregation
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
    public function getValue($name)
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        return $this->rawData[$name];
    }

    /**
     * Returns array of bucket values.
     *
     * @return ValueAggregation[]|null
     */
    public function getBuckets()
    {
        if (!isset($this->rawData['buckets'])) {
            return null;
        }

        $buckets = [];

        foreach ($this->rawData['buckets'] as $bucket) {
            $buckets[] = new ValueAggregation($bucket);
        }

        return $buckets;
    }

    /**
     * Returns sub-aggregation.
     *
     * @param string $name
     *
     * @return ValueAggregation|null
     */
    public function getAggregation($name)
    {
        // TODO: remove this *** after DSL update
        $name = AbstractAggregation::PREFIX . $name;

        if (!isset($this->rawData[$name])) {
            return null;
        }

        return new ValueAggregation($this->rawData[$name]);
    }

    /**
     * Applies path method to aggregations.
     *
     * @param string $path
     *
     * @return ValueAggregation|null
     */
    public function find($path)
    {
        $name = explode('.', $path, 1);
        $aggregation = $this->getAggregation($name[0]);

        if ($aggregation === null || !isset($name[1])) {
            return $aggregation;
        }

        return $aggregation->find($name[1]);
    }
}
