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
     * @var array Extracted aggregation value.
     */
    private $value;

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
            if (strpos($key, AbstractAggregation::PREFIX) !== 0) {
                $this->value[$key] = $value;
            }
        }

        return $this->value;
    }

    /**
     * Returns sub-aggregations.
     *
     * @return null|AggregationIterator|ValueAggregation
     */
    public function getAggregations()
    {
        $rawAggregations = [];

        foreach ($this->rawData as $key => $value) {
            if (strpos($key, AbstractAggregation::PREFIX) === 0) {
                $rawAggregations[$key] = $value;
            }
        }

        return new AggregationIterator($rawAggregations);
    }

    /**
     * Applies path method to aggregations.
     *
     * @param string $path
     *
     * @return null|AggregationIterator|ValueAggregation
     */
    public function find($path)
    {
        $iterator = $this->getAggregations();

        return $iterator->find($path);
    }
}
