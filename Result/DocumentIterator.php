<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;

/**
 * Class DocumentIterator.
 */
class DocumentIterator extends AbstractConvertibleResultIterator implements \Countable, \ArrayAccess, \Iterator
{
    use CountableTrait;
    use ArrayAccessTrait;
    use IteratorTrait;
    use ConverterAwareTrait;

    /**
     * @var array
     */
    private $typesMapping;

    /**
     * @var array
     */
    private $bundlesMapping;

    /**
     * @var array
     */
    private $rawAggregations;

    /**
     * @var AggregationIterator
     */
    private $aggregations;

    /**
     * @var array
     */
    private $rawSuggestions;

    /**
     * Constructor.
     *
     * @param array $rawData
     * @param array $typesMapping
     * @param array $bundlesMapping
     */
    public function __construct($rawData, $typesMapping, $bundlesMapping)
    {
        parent::__construct($rawData);

        $this->typesMapping = $typesMapping;
        $this->bundlesMapping = $bundlesMapping;

        if (isset($rawData['aggregations'])) {
            $this->rawAggregations = &$rawData['aggregations'];
        }

        if (isset($rawData['suggest'])) {
            $this->rawSuggestions = &$rawData['suggest'];
        }
    }

    /**
     * @return array
     */
    protected function getTypesMapping()
    {
        return $this->typesMapping;
    }

    /**
     * @return array
     */
    protected function getBundlesMapping()
    {
        return $this->bundlesMapping;
    }

    /**
     * Returns aggregations.
     *
     * @return AggregationIterator
     */
    public function getAggregations()
    {
        if (isset($this->rawAggregations)) {
            $data = [];

            foreach ($this->rawAggregations as $key => $value) {
                $realKey = substr($key, strlen(AbstractAggregation::PREFIX));
                $data[$realKey] = $value;
            }

            unset($this->rawAggregations);
            $this->aggregations = new AggregationIterator($data, $this->getConverter());
        } elseif ($this->aggregations === null) {
            $this->aggregations = new AggregationIterator([]);
        }

        return $this->aggregations;
    }
}
