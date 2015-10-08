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
use ONGR\ElasticsearchBundle\Result\Converter;

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
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param array     $rawData
     * @param Converter $converter
     */
    public function __construct($rawData, $converter = null)
    {
        $this->rawData = $rawData;
        $this->converter = $converter;
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
     * @return AggregationIterator
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
     * @return AggregationIterator
     */
    public function find($path)
    {
        $iterator = $this->getAggregations();

        return $iterator->find($path);
    }
}
