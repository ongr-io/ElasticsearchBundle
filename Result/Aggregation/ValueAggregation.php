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

use ONGR\ElasticsearchBundle\DSL\Aggregation\Aggregations;

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
     * Extracted aggregation value.
     *
     * @var array
     */
    private $value;

    /**
     * @var AggregationIterator
     */
    private $aggregations;

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
     * Returns aggregation value.
     *
     * @return array
     */
    public function getValue()
    {
        if ($this->value !== null) {
            return $this->value;
        }

        $this->value = [];

        foreach ($this->rawData as $key => $value) {
            if (strpos($key, Aggregations::PREFIX) !== 0) {
                $this->value[$key] = $value;
                // Clear memory.
                unset($this->rawData[$key]);
            }
        }

        return $this->value;
    }

    /**
     * Returns sub-aggregations.
     *
     * @return AggregationIterator
     */
    public function getAggregations()
    {
        if ($this->aggregations !== null) {
            return $this->aggregations;
        }

        $data = [];

        foreach ($this->rawData as $key => $value) {
            if (strpos($key, Aggregations::PREFIX) === 0) {
                $realKey = substr($key, strlen(Aggregations::PREFIX));
                $data[$realKey] = $value;
                // Clear memory.
                unset($this->rawData[$key]);
            }
        }

        $this->aggregations = new AggregationIterator($data);

        return $this->aggregations;
    }

    /**
     * Applies path method to aggregations.
     *
     * @param string $path
     *
     * @return AggregationIterator
     */
    public function find($path)
    {
        $iterator = $this->getAggregations();

        return $iterator->find($path);
    }
}
